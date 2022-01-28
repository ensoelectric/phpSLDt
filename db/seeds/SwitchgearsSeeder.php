<?php


use Phinx\Seed\AbstractSeed;

class SwitchgearsSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
        {
            $faker = Faker\Factory::create('ru_RU');
            $data = [];
            for ($i = 1; $i <= 1000; $i++) {
                $data[] = [
                    'id' => $i,
                    'label' => $faker->numerify('ADBO ###'),
                    'enclosure_model' => $faker->randomElement(['ABB ComfortLine','ABB System pro E Energy','ABB System pro E Control','ABB MISTRAL41F','ABB MISTRAL41W','ABB MISTRAL65','ABB basic E','ABB UK600','ABB UK500','SE Pragma','SE Easy9 Box','SE Kaedra','SE Mini Kaedra','DKC ST','DKC CE','DKC DAE','DKC CQE']),
                    'enclosure_article' => $faker->numerify('Enclosure article ###'),
                    'enclosure_construction' => $faker->randomElement(['Wall-mounted', 'Flush-mounted', 'Floor-mounted']),
                    'enclosure_protection_class' => $faker->randomElement(['IP31', 'IP44', 'IP54', 'IP65', 'IP67', 'IP68']),
                    'location' => $faker->address(),
                    'phases' => $faker->randomElement(['1', '3']),
                    'ground' => $faker->randomElement(['TN-C', 'TN-C-S', 'TN-S', 'TT', 'IT']),
                    'din_modules' => $faker->numberBetween($min = 1, $max = 100),
                    'installed_capacity' => $faker->numberBetween($min = 1, $max = 100),
                    'installed_current' => $faker->numberBetween($min = 1, $max = 100),
                    'estimated_power' => $faker->numberBetween($min = 1, $max = 100),
                    'estimated_current' =>$faker->numberBetween($min = 1, $max = 100),
                    'demand_factor' => "0.".$faker->numberBetween($min = 1, $max = 100),
                    'installed_current_a' => $faker->numberBetween($min = 1, $max = 100),
                    'installed_current_b' => $faker->numberBetween($min = 1, $max = 100),
                    'installed_current_c' => $faker->numberBetween($min = 1, $max = 100),
                    'supplier_switchgear_label' => $faker->numerify('DBO ###'),
                    'supplier_device' => $faker->randomElement(['MCB', 'RCD', 'RCCB', 'MCCB', 'ELCB', 'RCB', 'RCCB']),
                    'supplier_device_rating' => $faker->numberBetween($min = 1, $max = 100),
                    'supplier_device_trip_settings' => $faker->numberBetween($min = 1, $max = 100),
                    'supplier_device_interrupting_rating' => $faker->randomElement(['4.5', '6', '10', '15']),
                    'supplier_device_type' => $faker->randomElement(['A', 'B', 'C', 'D', 'L', 'Z', 'K']),
                    'supplier_device_poles' => $faker->numberBetween($min = 1, $max = 4),
                    'supplier_device_leakage_current_settings' => $faker->numberBetween($min = 1, $max = 100),
                    'supplier_device_label' => $faker->numerify('QF ##'),
                    'supplier_cable_info' => "Информация о кабеле, которым запитано данное распред. устройтво приведена в схеме распред. устройства, осуществляющего электропитание",
                    'draft' => FALSE //'draft' => $faker->boolean()
                ];
            }
            
            $applications = $this->table('applications');
            
            $switchgears = $this->table('switchgears');
            
            // empty the table
            $this->execute("SET foreign_key_checks=0;");
            $applications->truncate();
            $switchgears->truncate();
            $this->execute("SET foreign_key_checks=1;");
            
        
            $switchgears->insert($data)->saveData();
        }
}


