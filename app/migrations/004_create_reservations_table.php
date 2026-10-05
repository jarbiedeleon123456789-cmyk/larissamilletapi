<?php

class Create_reservations_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('reservations')) {
            return;
        }

        $this->_lava->dbforge
            ->add_field([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => TRUE,
                    'auto_increment' => TRUE,
                    'null'           => FALSE,
                ],
                'guest_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => FALSE,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => FALSE,
                ],
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => TRUE,
                ],
                'reserve_date' => [
                    'type' => 'DATE',
                    'null' => FALSE,
                ],
                'reserve_time' => [
                    'type' => 'TIME',
                    'null' => FALSE,
                ],
                'party_size' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => FALSE,
                    'default'    => 2,
                ],
                'note' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'pending','confirmed','cancelled'",
                    'null'       => FALSE,
                    'default'    => 'pending',
                ],
                'created_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => FALSE,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
            ])
            ->add_key('id', primary: TRUE)
            ->add_key('reserve_date', name: 'reserve_date_idx')
            ->create_table('reservations');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('reservations');
    }
}
