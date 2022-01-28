<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSwitchgearsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('switchgears');
        $table->addColumn('label', 'string', [ 'limit' => 50 ] )
              ->addColumn('enclosure_model', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('enclosure_article', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('enclosure_construction', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('enclosure_protection_class', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('location', 'string', [ 'limit' => 255, 'null' => true ])
              ->addColumn('phases', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('ground', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('din_modules', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('installed_capacity', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('installed_current', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('estimated_power', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('estimated_current', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('demand_factor', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('installed_current_a', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('installed_current_b', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('installed_current_c', 'string', [ 'limit' => 50, 'null' => true ])
              ->addColumn('supplier_switchgear_label', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_rating', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_trip_settings', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_interrupting_rating', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_type', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_poles', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_leakage_current_settings', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_device_label', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('supplier_cable_info', 'string', [ 'limit' => 50, 'null' => true ] )
              ->addColumn('draft', 'smallinteger', ['default' => '0'])
              ->addColumn('deleted_at', 'timestamp', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
