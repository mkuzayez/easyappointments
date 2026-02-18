<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.2
 * ---------------------------------------------------------------------------- */

/**
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Add_completed_appointment_status extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $row = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        if ($row) {
            $options = json_decode($row['value'], true);

            if (is_array($options) && !in_array('Completed', $options)) {
                $options[] = 'Completed';

                $this->db->where('name', 'appointment_status_options')
                    ->update('settings', ['value' => json_encode($options)]);
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $row = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        if ($row) {
            $options = json_decode($row['value'], true);

            if (is_array($options)) {
                $options = array_values(array_filter($options, fn($o) => $o !== 'Completed'));

                $this->db->where('name', 'appointment_status_options')
                    ->update('settings', ['value' => json_encode($options)]);
            }
        }
    }
}
