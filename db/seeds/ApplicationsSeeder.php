<?php


use Phinx\Seed\AbstractSeed;

class ApplicationsSeeder extends AbstractSeed
{
    public function getDependencies()
    {
        return [
            'SwitchgearsSeeder'
        ];
    }
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
        $faker = Faker\Factory::create();
        $data = [];
            for ($i = 1; $i <= 1000; $i++) {
                $count = rand(0,15);
                
                for ($j = 0; $j < $count; $j++){
                    $data[] = [
                        'switchgear_id'=> $i,
                        'position' => $j,
                        'device'=>$faker->numerify('QF ##'),
                        'label'=>$faker->numerify('X-#'),
                        'desc'=>$faker->realText(50),
                        'cable_label'=>$faker->numerify('Гр. #'),
                        'cable_model'=> $faker->randomElement(['ВВГ 3х6', 'ВВГ 3х10', 'ВВГ 3х16', 'ВВГ 3х25', 'ВВГ 3х35', 'ВВГ 3х50', 'ВВГ 3х70', 'ВВГ 3х95', 'ВВГ 3х120', 'ВВГ 3х150', 'ВВГ 3х185', 'ВВГ 3х240', 'ВВГ 3х2,5+1х1,5', 'ВВГ 3х4+1х2,5', 'ВВГ 3х6+1х4']),
                        'cable_length'=> $faker->numberBetween($min = 10, $max = 50),
                        'pipe_label'=>$faker->numerify('Т ##'),
                        'pipe_length'=> $faker->randomDigit(),
                        'installed_capacity'=> $faker->numberBetween($min = 1, $max = 100),
                        'installed_current_a'=>$faker->numberBetween($min = 1, $max = 100),
                        'installed_current_b'=> $faker->numberBetween($min = 1, $max = 100),
                        'installed_current_c'=> $faker->numberBetween($min = 1, $max = 100),
                        'power_factor'=> "0.".$faker->numberBetween($min = 60, $max = 98)
                    ];
                }                    
            }
            
            $switchgears = $this->table('applications');
            $switchgears->insert($data)->saveData();
    }
}
