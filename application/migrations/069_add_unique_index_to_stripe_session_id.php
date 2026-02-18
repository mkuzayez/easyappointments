<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

class Migration_Add_unique_index_to_stripe_session_id extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        // Drop the existing non-unique index if present, then add unique indexes.
        $indexes = $this->db->query(
            'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'idx_stripe_session'"
        )->result_array();

        if (!empty($indexes)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP INDEX idx_stripe_session',
            );
        }

        // Add unique indexes to prevent session/payment intent replay.
        $this->db->query(
            'ALTER TABLE ' .
                $this->db->dbprefix('appointments') .
                ' ADD UNIQUE INDEX ux_stripe_session_id (stripe_session_id)',
        );

        $this->db->query(
            'ALTER TABLE ' .
                $this->db->dbprefix('appointments') .
                ' ADD UNIQUE INDEX ux_stripe_payment_intent_id (stripe_payment_intent_id)',
        );
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $indexes_session = $this->db->query(
            'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'ux_stripe_session_id'"
        )->result_array();

        if (!empty($indexes_session)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP INDEX ux_stripe_session_id',
            );
        }

        $indexes_intent = $this->db->query(
            'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'ux_stripe_payment_intent_id'"
        )->result_array();

        if (!empty($indexes_intent)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP INDEX ux_stripe_payment_intent_id',
            );
        }

        // Restore the original non-unique index.
        $this->db->query(
            'ALTER TABLE ' .
                $this->db->dbprefix('appointments') .
                ' ADD INDEX idx_stripe_session (stripe_session_id)',
        );
    }
}
