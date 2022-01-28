<?php
namespace ru\ensoelectic\phpSLDt;

class Applications
{
    private $db;
    private $records;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create(string $file): void
    {
        $request_array = Helpers::json_decode($file, true, 512);

        $this->insert($request_array);
    }

    public function update(string $id, string $file): void
    {
        list($switchgear, $position) = explode("-", $id);

        $array = Helpers::json_decode($file, true, 512);

        //if $array["position"]> MAX(position)? then error

        $prepare_array = [
            $array["position"] ?? $position,
            $array["device"] ?? null,
            $array["label"] ?? null,
            $array["desc"] ?? null,
            $array["cable"]["label"] ?? null,
            $array["cable"]["model"] ?? null,
            $array["cable"]["length"] ?? null,
            $array["pipe"]["label"] ?? null,
            $array["pipe"]["length"] ?? null,
            $array["load"]["installed"]["capacity"] ?? null,
            $array["load"]["installed"]["current_a"] ?? null,
            $array["load"]["installed"]["current_b"] ?? null,
            $array["load"]["installed"]["current_c"] ?? null,
            $array["load"]["power_factor"] ?? null,
            $switchgear,
            $position,
        ];

        try {
            $stmt = $this->db->prepare(
                "UPDATE `applications` SET `position`=?, `device`=?, `label`=?, `desc`=?, `cable_label`=?, `cable_model`=?, `cable_length`=?, `pipe_label`=?, `pipe_length`=?, `installed_capacity`=?, `installed_current_a`=?, `installed_current_b`=?, `installed_current_c`=?, `power_factor`=? WHERE `switchgear_id`=? AND `position`=?;"
            );

            $stmt->execute($prepare_array);
        } catch (\PDOException $e) {
            $errorInfo = $e->errorInfo;

            if ($errorInfo[1] != 1062) throw $e; //Duplicate entry '%s' for key 'PRIMARY'

            $this->delete($id);

            $this->insert(array_merge(["diagram" => $switchgear], $array));
        }
    }

    public function delete(string $id): void
    {
        list($switchgear, $position) = explode("-", $id);

        $stmt = $this->db->prepare(
            "DELETE `applications` FROM `applications` INNER JOIN `switchgears` ON `switchgears`.`id`=? AND `switchgears`.`deleted_at` IS NULL WHERE `applications`.`switchgear_id`=`switchgears`.`id` AND `applications`.`position`=?;"
        );

        $stmt->execute([$switchgear, $position]);

        if ($stmt->rowCount() == 0) throw new \Exception("Not found", 404);

        $this->moveUp($switchgear, $position);
    }

    public function options(string $id = null): array
    {
        if (empty($id)) return ["POST", "OPTIONS"];

        list($switchgear, $position) = array_pad(explode("-", $id), 2, null);

        $stmt = $this->db->prepare(
            "SELECT * FROM `applications` RIGHT JOIN `switchgears` ON `switchgears`.`id` = `applications`.`switchgear_id` AND `switchgears`.`deleted_at` IS NULL WHERE `switchgear_id`=? AND `position`=?"
        );

        $stmt->execute([$switchgear, $position]);

        if ($stmt->rowCount() == 0) {
            throw new \Exception("Not found", 404);
        }

        return ["PUT", "DELETE", "OPTIONS"];
    }

    private function insert(array $array, string $created_at = null): void
    {
        $prepare_array = [
            $array["diagram"] ?? null,
            $array["position"] ?? null,
            $array["device"] ?? null,
            $array["label"] ?? null,
            $array["desc"] ?? null,
            $array["cable"]["label"] ?? null,
            $array["cable"]["model"] ?? null,
            $array["cable"]["length"] ?? null,
            $array["pipe"]["label"] ?? null,
            $array["pipe"]["length"] ?? null,
            $array["load"]["installed"]["capacity"] ?? null,
            $array["load"]["installed"]["current_a"] ?? null,
            $array["load"]["installed"]["current_b"] ?? null,
            $array["load"]["installed"]["current_c"] ?? null,
            $array["load"]["power_factor"] ?? null,
            $created_at ?? null,
        ];

        try {
            if (empty($prepare_array[1])) {
                $stmt = $this->db->prepare(
                    "SELECT MAX(`position`)+1 FROM `applications` WHERE switchgear_id=?"
                );
                $stmt->execute([$prepare_array[0]]);
                $prepare_array[1] = $stmt->fetchColumn() ?? 0;
            }
            $stmt = $this->db->prepare(
                "INSERT INTO `applications` (`switchgear_id`, `position`, `device`, `label`, `desc`, `cable_label`, `cable_model`, `cable_length`, `pipe_label`, `pipe_length`, `installed_capacity`, `installed_current_a`, `installed_current_b`, `installed_current_c`, `power_factor`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);"
            );

            $stmt->execute($prepare_array);
        } catch (\PDOException $e) {
            $errorInfo = $e->errorInfo;

            if ($errorInfo[1] != 1062) throw $e; //Duplicate entry '%s' for key 'PRIMARY'

            $this->moveDown($prepare_array[0], $prepare_array[1]);

            $stmt->execute($prepare_array);
        }
    }

    private function moveUp(int $switchgear, int $position): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `applications` SET `position` = `position` - 1 WHERE `switchgear_id`=? AND `position`> ? ORDER BY `position` ASC;"
        );

        $stmt->execute([$switchgear, $position]);
    }

    private function moveDown(int $switchgear, int $position): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `applications` SET `position` = `position` + 1 WHERE `switchgear_id`=? AND `position`>= ? ORDER BY `position` DESC;"
        );

        $stmt->execute([$switchgear, $position]);
    }
}