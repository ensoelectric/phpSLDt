<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateApplicationsTable extends AbstractMigration
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
        $table = $this->table('applications', ['id' => false, 'primary_key' => ['switchgear_id', 'position']]);
        $table->addColumn('switchgear_id', 'integer')
                ->addColumn('position', 'integer' )
                ->addColumn('label', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('desc', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('cable_label', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('cable_model', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('cable_length', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('pipe_label', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('pipe_length', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('installed_capacity', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('installed_current_a', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('installed_current_b', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('installed_current_c', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('power_factor', 'string', [ 'limit' => 50, 'null' => true ] )
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('switchgear_id', 'switchgears', 'id', ['update'=> 'CASCADE'])
                ->create();
    }
}
