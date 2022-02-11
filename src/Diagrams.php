<?php
namespace ru\ensoelectic\phpSLDt;

class Diagrams
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll(int $page = 1, int $per_page = 150, string $search = null): string
    {
        $diagrams = empty($search) ? $this->getAll($page, $per_page) : $this->search($page, $per_page, $search);

        return json_encode($diagrams);
    }

    public function find(int $id, string $http_accept = null): string
    {
        $stmt = $this->db->prepare(
            "SELECT `switchgears`.`id`, `switchgears`.`label` as 'switchgear_label', `switchgears`.`enclosure_model`,`switchgears`.`enclosure_article`, `switchgears`.`enclosure_construction`, `switchgears`.`enclosure_protection_class`, `switchgears`. `location`, `switchgears`.`phases`, `switchgears`.`ground`, `switchgears`.`din_modules`, `switchgears`.`installed_capacity` AS 'switchgear_installed_capacity', `switchgears`.`installed_current`, `switchgears`.`estimated_power`, `switchgears`.`estimated_current`, `switchgears`.`demand_factor`, `switchgears`.`installed_current_a`  AS 'switchgear_installed_current_a',`switchgears`.`installed_current_b`   AS 'switchgear_installed_current_b',`switchgears`.`installed_current_c`   AS 'switchgear_installed_current_c', `switchgears`.`supplier_switchgear_label`, `switchgears`.`supplier_device`, `switchgears`.`supplier_device_rating`, `switchgears`.`supplier_device_trip_settings`, `switchgears`.`supplier_device_interrupting_rating`, `switchgears`.`supplier_device_type`, `switchgears`.`supplier_device_poles`, `switchgears`.`supplier_device_leakage_current_settings`, `switchgears`.`supplier_device_label`, `switchgears`.`supplier_cable_info`, `switchgears`.`draft`, `switchgears`.`deleted_at`, `switchgears`.`created_at` AS 'switchgear_created_at', `switchgears`.`updated_at` as 'switchgear_updated_at',  `applications`.`switchgear_id`,`applications`.`device` as 'application_device',  `applications`.`label` as 'application_label', `applications`.`desc`, `applications`.`cable_label`, `applications`.`cable_model`, `applications`.`cable_length`, `applications`.`pipe_label`, `applications`.`pipe_length`, `applications`.`installed_capacity`, `applications`.`installed_current_a`, `applications`.`installed_current_b`, `applications`.`installed_current_c`, `applications`.`power_factor`, `applications`.`created_at`, `applications`.`updated_at` FROM `switchgears` LEFT JOIN `applications` ON `applications`.`switchgear_id`=`switchgears`.`id` WHERE `switchgears`.`deleted_at` IS NULL AND `switchgears`.`id`=? ORDER BY `applications`.`position` ASC"
        );

        $stmt->execute([$id]);

        while ($row = $stmt->fetch(\PDO::FETCH_LAZY)) {
            if (!isset($diagram)) $diagram = $this->diagramObjToArray($row);

            if (!empty($row["switchgear_id"])) $diagram["applications"][] = $this->applicationObjToArray($row);
        }

        if (!isset($diagram)) throw new \Exception("Not found", "404");

        if ($http_accept == "application/pdf"){
          try{
            $this->generatePDF($diagram);
            die();
          } catch(Exception $e){
            throw $e;
          }
        }

        return json_encode($diagram);
    }

    public function create(string $file): int
    {
        $request_array = Helpers::json_decode($file, true, 512);

        $this->insert($request_array);

        if (!is_array($request_array["applications"] ?? null) || empty($request_array["applications"])) return $this->db->lastInsertId();

        $last_id = $this->db->lastInsertId();

        $applications = new Applications($this->db);

        foreach ($request_array["applications"] as $application) {
            $array = array_merge(["diagram" => $last_id], $application);

            $applications->create(json_encode($array));
        }

        return $last_id;
    }

    public function update(string $id, string $file): void
    {
        $array = Helpers::json_decode($file, true, 512);

        if ($this->issetDiagram($id)) throw new \Exception("Not found", 404);

        $stmt = $this->db->prepare(
            "UPDATE `switchgears` SET `label`=?, `enclosure_model`=?, `enclosure_article`=?, `enclosure_construction`=?, `enclosure_protection_class`=?, `location`=?, `phases`=?, `ground`=?, `din_modules`=?, `installed_capacity`=?, `installed_current`=?, `estimated_power`=?, `estimated_current`=?, `demand_factor`=?, `installed_current_a`=?, `installed_current_b`=?, `installed_current_c`=?, `supplier_switchgear_label`=?, `supplier_device`=?, `supplier_device_rating`=?, `supplier_device_trip_settings`=?, `supplier_device_interrupting_rating`=?, `supplier_device_type`=?, `supplier_device_poles`=?, `supplier_device_leakage_current_settings`=?, `supplier_device_label`=?, `supplier_cable_info`=? WHERE `id`=? AND `deleted_at` IS NULL;"
        );

        $stmt->execute(array_merge($this->buildArrayForCreateAndUpdate($array), [$id]));

        foreach ($array["applications"] ?? [] as $position => $application) {
            //Consider the need to add a check for uniqueness 'switchgear_id' and 'position' in the resulting JSON
            $apps[$position] = [
                $id,
                $position,
                $application["device"] ?? null,
                $application["label"] ?? null,
                $application["desc"] ?? null,
                $application["cable"]["label"] ?? null,
                $application["cable"]["model"] ?? null,
                $application["cable"]["length"] ?? null,
                $application["pipe"]["label"] ?? null,
                $application["pipe"]["length"] ?? null,
                $application["load"]["installed"]["capacity"] ?? null,
                $application["load"]["installed"]["current"] ?? null,
                $application["load"]["installed"]["current_a"] ?? null,
                $application["load"]["installed"]["current_b"] ?? null,
                $application["load"]["installed"]["power_factor"] ?? null,
            ];
        }

        $this->deleteApplications($id, array_keys($apps ?? []));

        foreach ($apps ?? [] as $app) {
            $stmt = $this->db->prepare(
                "INSERT INTO `applications` (`switchgear_id`, `position`, `device`, `label`, `desc`, `cable_label`, `cable_model`, `cable_length`, `pipe_label`, `pipe_length`, `installed_capacity`, `installed_current_a`, `installed_current_b`, `installed_current_c`, `power_factor`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `device`=VALUES(`device`), `label`=VALUES(`label`), `desc` = VALUES(`desc`), `cable_label`=VALUES(`cable_label`), `cable_model`=VALUES(`cable_model`), `cable_length`=VALUES(`cable_length`), `pipe_label`=VALUES(`pipe_label`), `pipe_length`=VALUES(`pipe_length`), `installed_capacity`=VALUES(`installed_capacity`), `installed_current_a`=VALUES(`installed_current_a`), `installed_current_b`=VALUES(`installed_current_b`), `installed_current_c`=VALUES(`installed_current_c`), `power_factor`=VALUES(`power_factor`);"
            );

            $stmt->execute($app);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `switchgears` SET `deleted_at` = current_timestamp() WHERE `switchgears`.`deleted_at` IS NULL AND `id`=?;"
        );

        $stmt->execute([$id]);

        if ($stmt->rowCount() == 0) throw new \Exception("Not found", 404);
    }

    public function options(string $id = null): array
    {
        if (empty($id)) return ["GET", "POST", "OPTIONS"];

        $stmt = $this->db->prepare(
            "SELECT * FROM `switchgears` WHERE `id` = ? AND `deleted_at` IS NULL"
        );

        $stmt->execute([$id]);

        if ($stmt->rowCount() == 0) throw new \Exception("Not found", 404);

        return ["GET", "PUT", "DELETE", "OPTIONS"];
    }

    private function getAll(int $page, int $per_page): array
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM `switchgears` WHERE `deleted_at` IS NULL AND `draft` = 0"
        );
        $total_count = $stmt->fetchColumn();

        Headers::getInstance()->add("X-Total-Count: $total_count");

        if ($total_count <= 0) return [];

        $stmt = $this->db->prepare(
            "SELECT * FROM `switchgears` WHERE `deleted_at` IS NULL AND `draft` = 0 ORDER BY `created_at` DESC LIMIT ? OFFSET ?;"
        );

        $stmt->execute([$per_page, $per_page * ($page - 1)]);

        while ($row = $stmt->fetch(\PDO::FETCH_LAZY)) {
            $diagrams[] = $this->diagramObjToArray($row, true);
        }

        Headers::getInstance()->add("Link: " .Helpers::rfc5988Link($_SERVER["HTTP_HOST"]."" .strtok($_SERVER["REQUEST_URI"], "?"), $total_count, $page, $per_page));

        return $diagrams;
    }

    private function search(int $page, int $per_page, string $search): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `switchgears` WHERE `deleted_at` IS NULL AND `draft` = 0 AND CONCAT(`label`, `location`) LIKE ?"
        );
        $stmt->execute(["%$search%"]);
        $total_count = $stmt->fetchColumn();

        Headers::getInstance()->add("X-Total-Count: $total_count");

        if ($total_count <= 0) return [];

        $stmt = $this->db->prepare(
            "SELECT * FROM `switchgears` WHERE `deleted_at` IS NULL AND `draft` = 0 AND CONCAT(`label`, `location`) LIKE ? ORDER BY `created_at` DESC LIMIT ? OFFSET ?;"
        );

        $stmt->execute(["%$search%", $per_page, $per_page * ($page - 1)]);

        while ($row = $stmt->fetch(\PDO::FETCH_LAZY)) {
            $diagrams[] = $this->diagramObjToArray($row, true);
        }

        Headers::getInstance()->add("Link: " .Helpers::rfc5988Link($_SERVER["HTTP_HOST"]."".strtok($_SERVER["REQUEST_URI"], "?"), $total_count, $page, $per_page));

        return $diagrams;
    }
    
    private function insert(array $array): void
    {
        $stmt = $this->db->prepare(
            " INSERT INTO `switchgears` (`label`, `enclosure_model`, `enclosure_article`, `enclosure_construction`, `enclosure_protection_class`, `location`, `phases`, `ground`, `din_modules`, `installed_capacity`, `installed_current`, `estimated_power`, `estimated_current`, `demand_factor`, `installed_current_a`, `installed_current_b`, `installed_current_c`, `supplier_switchgear_label`, `supplier_device`, `supplier_device_rating`, `supplier_device_trip_settings`, `supplier_device_interrupting_rating`, `supplier_device_type`, `supplier_device_poles`, `supplier_device_leakage_current_settings`, `supplier_device_label`, `supplier_cable_info`, `draft`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0');"
        );

        $stmt->execute($this->buildArrayForCreateAndUpdate($array));
    }

    private function diagramObjToArray(object $obj, bool $id = false): array
    {
        $array = [
            "id" => $obj->id,
            "label" => $obj->switchgear_label ?? $obj->label,
            "location" => $obj->location,
            "phases" => $obj->phases,
            "ground" => $obj->ground,
            "created_at" => $obj->created_at,
            "updated_at" => $obj->updated_at,
        ];

        if (!$id) {
            array_shift($array);
        }

        $array["enclosure"] = [
            "model" => $obj->enclosure_model,
            "article" => $obj->enclosure_article,
            "construction" => $obj->enclosure_construction,
            "protection" => $obj->enclosure_protection_class,
            "modules" => $obj->din_modules,
        ];

        $array["load"]["demand_factor"] = $obj->demand_factor;

        $array["load"]["installed"] = [
            "capacity" => $obj->switchgear_installed_capacity,
            "current" => $obj->installed_current,
            "current_a" => $obj->switchgear_installed_current_a,
            "current_b" => $obj->switchgear_installed_current_b,
            "current_c" => $obj->switchgear_installed_current_c,
        ];

        $array["load"]["estimated"] = [
            "power" => $obj->estimated_power,
            "current" => $obj->estimated_current,
        ];

        $array["supplier"] = [
            "label" => $obj->supplier_switchgear_label,
            "cable" => $obj->supplier_cable_info,
        ];

        $array["supplier"]["device"] = [
            "label" => $obj->supplier_device_label,
            "device" => $obj->supplier_device,
            "rating" => $obj->supplier_device_rating,
            "trip_settings" => $obj->supplier_device_trip_settings,
            "interrupting_rating" => $obj->supplier_device_interrupting_rating,
            "type" => $obj->supplier_device_type,
            "poles" => $obj->supplier_device_poles,
            "leakage_current_settings" => $obj->supplier_device_leakage_current_settings,
        ];
        return $array;
    }

    private function applicationObjToArray(object $obj): array
    {
        $array = [
            "device" => $obj->application_device,
            "label" => $obj->application_label,
            "desc" => $obj->desc,
        ];

        $array["cable"] = [
            "label" => $obj->cable_label,
            "model" => $obj->cable_model,
            "length" => $obj->cable_length,
        ];

        $array["pipe"] = [
            "label" => $obj->pipe_label,
            "length" => $obj->pipe_length,
        ];

        $array["load"]["installed"] = [
            "capacity" => $obj->installed_capacity,
            "current" => $obj->installed_current,
            "current_a" => $obj->installed_current_a,
            "current_b" => $obj->installed_current_b,
            "current_c" => $obj->installed_current_c,
            "power_factor" => $obj->power_factor,
        ];

        return $array;
    }

    private function buildArrayForCreateAndUpdate(array $array): array
    {
        return [
            $array["label"] ?? null,
            $array["enclosure"]["model"] ?? null,
            $array["enclosure"]["article"] ?? null,
            $array["enclosure"]["construction"] ?? null,
            $array["enclosure"]["protection"] ?? null,
            $array["location"] ?? null,
            $array["phases"] ?? null,
            $array["ground"] ?? null,
            $array["enclosure"]["modules"] ?? null,
            $array["load"]["installed"]["capacity"] ?? null,
            $array["load"]["installed"]["current"] ?? null,
            $array["load"]["estimated"]["power"] ?? null,
            $array["load"]["estimated"]["current"] ?? null,
            $array["load"]["demand_factor"] ?? null,
            $array["load"]["installed"]["current_a"] ?? null,
            $array["load"]["installed"]["current_b"] ?? null,
            $array["load"]["installed"]["current_c"] ?? null,
            $array["supplier"]["label"] ?? null,
            $array["supplier"]["device"]["device"] ?? null,
            $array["supplier"]["device"]["rating"] ?? null,
            $array["supplier"]["device"]["trip_settings"] ?? null,
            $array["supplier"]["device"]["interrupting_rating"] ?? null,
            $array["supplier"]["device"]["type"] ?? null,
            $array["supplier"]["device"]["poles"] ?? null,
            $array["supplier"]["device"]["leakage_current_settings"] ?? null,
            $array["supplier"]["device"]["label"] ?? null,
            $array["supplier"]["cable"] ?? null,
        ];
    }

    private function deleteApplications(int $switchgear_id, array $app_ids = null): void 
    {
        if (!is_array($app_ids) || empty($app_ids)) {
            
            $stmt = $this->db->prepare(
                "DELETE FROM `applications` WHERE `switchgear_id` = ?;"
            );
            
            $stmt->execute([$switchgear_id]);
            
            return;
        }

        $stmt = $this->db->prepare(
            "DELETE FROM `applications` WHERE `switchgear_id` = ? AND `position` NOT IN (".str_repeat("?, ", count($app_ids) - 1) ."?);"
        );
        
        $stmt->execute(array_merge([$switchgear_id], $app_ids));
    }

    private function issetDiagram(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `switchgears` WHERE `deleted_at` IS NULL AND `id`=?"
        );

        $stmt->execute([$id]);

        return empty($stmt->fetchColumn()) ? true : false;
    }

    private function generatePDF(array $diagram)
    {
      $thead =
          '<h1 style="font-family: sans-serif; font-size: 1em;  text-align: center;">Принципиальная схема групповой сети. ГОСТ 21.613-2014</h1>
          <table width="100%"  cellpadding=0  style=" font-family: sans-serif; font-size: 0.7em; border-collapse: collapse; border:1px dotted black">
            <tr>
              <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;"  colspan=2>Данные распределительного устройства</td>
              <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;"  colspan=2>Аппарат до ввода в распределительное устройство</td>
              <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;"  colspan=2>Данные об итоговых значениях нагрузок распед. устройства</td>
            </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Распределительное устройство</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["label"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Тип аппарата</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center" >' .$diagram["supplier"]["device"]["device"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;" rowspan=2>Установленная полная мощность распределительного устройства, кВА</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center"  rowspan=2>' .$diagram["load"]["installed"]["capacity"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Марка оболочки распред. устройства</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" >' .$diagram["enclosure"]["model"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px;">Номинальный ток, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" >' .$diagram["supplier"]["device"]["rating"] .'</td>

                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Код оболочки распред. устройства</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["enclosure"]["article"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Уставка расцепителя, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["trip_settings"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;">Ток от установленной мощности, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["load"]["installed"]["current"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Способ монтажа</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["enclosure"]["construction"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Предельная коммутационная стойкость, кА</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["interrupting_rating"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;" rowspan=2>Расчетная полная мощность распределительного устройства, кВА</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" rowspan=2>' .$diagram["load"]["estimated"]["power"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Степень защиты по ГОСТ14.254-96</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["enclosure"]["protection"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px;">Тип защитной характеристики</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["type"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Место установки распред. устройства</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">' .$diagram["location"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Количество отключаемых полюсов аппарата</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["poles"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;" rowspan=3>Расчетный ток от эквивалентной группы трехфазных электропотребителей с суммарной мощностью однофазных электропотребителей, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" rowspan=3>' .$diagram["load"]["estimated"]["current"] .'</td>
                </tr>
                 <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Количество фаз питания распред. устройства</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["phases"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Уставка дифференциального тока, мA</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["leakage_current_settings"] .'</td>
                </tr>
                 <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Тип питающей сети</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["ground"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Обозначение</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center">' .$diagram["supplier"]["device"]["label"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;" rowspan=2>Электропитание осуществляется от распределительного устройства</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" rowspan=2>' .$diagram["supplier"]["label"] .'</td>
                    <td style="color:#000000; border:1px dotted black; padding: 2px;" rowspan=4 colspan=2>' .$diagram["supplier"]["cable"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;">Усредненный коэффициент спроса</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px;text-align: center">' .$diagram["load"]["demand_factor"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;">Ток от установленной мощности в фазе А, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["load"]["installed"]["current_a"] .'</td>
                 <tr>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;" rowspan=2>Количество модулей по 17,5 мм, устанавливаемых в распределительном устройстве</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center" rowspan=2>' .$diagram["enclosure"]["modules"] .'</td>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;">Ток от установленной мощности в фазе B, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["load"]["installed"]["current_b"] .'</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 26%; padding: 2px;">Ток от установленной мощности в фазе C, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; padding: 2px; text-align: center">' .$diagram["load"]["installed"]["current_c"] .'</td>
                </tr>
            </table>';

        $applications =        
            '<div style="padding:10px"/>
            <table width="100%"  cellpadding=0  style=" font-family: sans-serif; border-collapse: collapse; border:1px dotted black">
            <thead style="background: #fc0">
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px;" ></td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;" colspan=3>Кабель, провод</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;" colspan=2>Труба</td>
                    <td style="color:#000000; border:1px dotted black; width: 16%; width: 16%; padding: 2px; text-align: center; font-weight: bold;" colspan=7>Электроприемник</td>
                </tr>
                <tr>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Аппарат отходящей линии(ввода); обозначение; тип; Iном, А; расцепитель или плавкая вставка, А; тип защитной характеристики; дифференциальный ток, мА</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Обозначение</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Марка</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Длина, м</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Обозначение</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Длина, м</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Обозначение</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Руст., или  Рном, кВт</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Фаза А: Iуст, или Iном, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Фаза B: Iуст, или Iном, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Фаза C: Iуст, или Iном, А</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">cos φ</td>
                    <td style="color:#000000; border:1px dotted black; width: 10%; padding: 2px; text-align: center;">Наименование, тип, обозначение чертежа принципиальной схемы</td>

                </tr>
            </thead>';

        foreach ($diagram["applications"] as $application) {
            $applications .=
                '<tr>
                    <td style="color:#000000; border:1px dotted black; width: 30%; padding: 2px;">' .$application["device"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["cable"]["label"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["cable"]["model"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["cable"]["length"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["pipe"]["label"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["pipe"]["length"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["label"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["load"]["installed"]["capacity"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["load"]["installed"]["current_a"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["load"]["installed"]["current_b"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["load"]["installed"]["current_c"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 7%; padding: 2px;">' .$application["load"]["installed"]["power_factor"].'</td>
                    <td style="color:#000000; border:1px dotted black; width: 30%; padding: 2px;">' .$application["desc"].'</td>
               </tr>';
        }

        $applications .= "</table>";
        
        $footer = 
          '<table style="width: 100%; font-family: sans-serif; font-size: 0.6em;">
            <tr>
              <td style="width: 50%">Generated by <b>phpSLDt</b> | Last updated: '.$diagram["updated_at"] .'</td>
              <td  style="width: 50%" align="right">{PAGENO} / {nbpg}</td>
            </tr>
          </table>';

        $mpdf = new \Mpdf\Mpdf(["mode" => "utf-8", "format" => "A4-L", 'tempDir' => '/tmp']);
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->SetHTMLFooter($footer);
        $mpdf->WriteHTML($thead);
        $mpdf->WriteHTML($applications);

        // Output a PDF file directly to the browser
        return $mpdf->Output('file.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    }
}
