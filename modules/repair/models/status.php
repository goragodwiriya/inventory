<?php
/**
 * @filesource modules/repair/models/status.php
 */

namespace Repair\Status;

use Kotchasan\Date;

class Model extends \Kotchasan\Model
{
    /**
     * Get a repair row with summary data.
     *
     * @param int $repairId
     *
     * @return object|null
     */
    public static function getRepairRow(int $repairId)
    {
        if ($repairId <= 0) {
            return null;
        }

        return static::createQuery()
            ->select(
                'R.id',
                'R.customer_id',
                'R.job_id',
                'R.product_no',
                'R.job_description',
                'R.created_at',
                'R.appointment_date',
                'R.appraiser',
                'R.informer',
                'U.name customer_name',
                'U.phone customer_phone',
                'V.topic'
            )
            ->from('repair R')
            ->join('user U', ['U.id', 'R.customer_id'], 'LEFT')
            ->join('inventory_items I', ['I.product_no', 'R.product_no'], 'LEFT')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->where(['R.id', $repairId])
            ->first();
    }

    /**
     * Build modal payload for view/process status.
     *
     * @param int $repairId
     * @param object $login
     * @param bool $canSubmit
     *
     * @return object|null
     */
    public static function getModalPayload(int $repairId, $login, bool $canSubmit)
    {
        $row = self::getRepairRow($repairId);
        if ($row === null || !\Repair\Helper\Controller::canViewRepair($login, $row)) {
            return null;
        }

        $latest = \Repair\Helper\Controller::getLatestStatusMap([$repairId])[$repairId] ?? null;
        $canSubmit = $canSubmit && \Repair\Helper\Controller::canProcessRepair($login);
        $canManageRepair = \Repair\Helper\Controller::canManageRepair($login);
        $historyRows = call_user_func([\Repair\Helper\Controller::class, 'getHistoryRows'], $repairId);

        return (object) [
            'id' => (int) $row->id,
            'customer_id' => (int) $row->customer_id,
            'job_id' => (string) $row->job_id,
            'product_no' => (string) $row->product_no,
            'topic' => (string) $row->topic,
            'customer_name' => (string) $row->customer_name,
            'customer_phone' => (string) $row->customer_phone,
            'job_description' => (string) $row->job_description,
            'created_at_text' => Date::format($row->created_at, 'd M Y H:i'),
            'appointment_date_text' => empty($row->appointment_date) ? '-' : Date::format($row->appointment_date, 'd M Y'),
            'current_status_text' => $latest ? \Repair\Helper\Controller::getStatusText((int) $latest->status) : '',
            'status' => $latest ? (int) $latest->status : \Repair\Helper\Controller::getFirstStatusId(),
            'operator_id' => $latest ? (int) $latest->operator_id : (int) ($login->id ?? 0),
            'comment' => '',
            'cost' => 0,
            'history_rows' => $historyRows,
            'can_submit' => $canSubmit,
            'can_manage_repair' => $canManageRepair
        ];
    }

    /**
     * Save a repair status log.
     *
     * @param int $repairId
     * @param array $save
     *
     * @return void
     */
    public static function saveStatus(int $repairId, array $save): void
    {
        $db = \Kotchasan\DB::create();
        $db->insert('repair_status', $save);
        $db->update('repair', ['id', $repairId], ['appraiser' => $save['cost']]);
    }
}