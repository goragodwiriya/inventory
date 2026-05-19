<?php
/**
 * @filesource modules/repair/controllers/helper.php
 */

namespace Repair\Helper;

use Gcms\Api as ApiController;

class Controller extends \Gcms\Controller
{
    /**
     * Get available repair statuses.
     *
     * @return array
     */
    public static function getStatusRows(): array
    {
        return array_map(static function ($row) {
            return [
                'value' => (string) $row->category_id,
                'text' => (string) $row->topic,
                'color' => (string) ($row->color ?? '')
            ];
        }, \Kotchasan\Model::createQuery()
                ->select('category_id', 'topic', 'color')
                ->from('category')
                ->where([
                    ['type', 'repairstatus'],
                    ['is_active', 1]
                ])
                ->orderBy('category_id')
                ->fetchAll());
    }

    /**
     * Status options for select controls.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return array_map(static function ($row) {
            return [
                'value' => $row['value'],
                'text' => $row['text']
            ];
        }, self::getStatusRows());
    }

    /**
     * Get status label by ID.
     *
     * @param int $status
     *
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        foreach (self::getStatusRows() as $row) {
            if ((int) $row['value'] === $status) {
                return $row['text'];
            }
        }

        return 'Unknown';
    }

    /**
     * Initial status ID.
     *
     * @return int
     */
    public static function getFirstStatusId(): int
    {
        if (!empty(self::$cfg->repair_first_status)) {
            return (int) self::$cfg->repair_first_status;
        }

        $options = self::getStatusRows();

        return isset($options[0]['value']) ? (int) $options[0]['value'] : 1;
    }

    /**
     * Repair operator options with a single extra included user.
     *
     * @param int|null $includeId
     *
     * @return array
     */
    public static function getOperatorOptions(?int $includeId = null): array
    {
        return self::buildOperatorOptions($includeId === null ? [] : [$includeId]);
    }

    /**
     * Repair operator options with multiple included users.
     *
     * @param array $includeIds
     *
     * @return array
     */
    public static function getOperatorOptionsByIds(array $includeIds): array
    {
        return self::buildOperatorOptions($includeIds);
    }

    /**
     * Build repair operator options.
     *
     * @param array $includeIds
     *
     * @return array
     */
    private static function buildOperatorOptions(array $includeIds): array
    {
        $includeIds = array_values(array_unique(array_filter(array_map('intval', $includeIds))));

        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'name')
            ->from('user')
            ->orderBy('name');

        if (!empty($includeIds)) {
            $query->whereRaw(sprintf('((active = 1 AND (permission LIKE :repair_permission OR permission LIKE :manage_permission)) OR id IN (%s))', implode(', ', $includeIds)), 'AND', [
                'repair_permission' => '%can_repair%',
                'manage_permission' => '%can_manage_repair%'
            ]);
        } else {
            $query->where(['active', 1]);
            $query->whereRaw('((permission LIKE :repair_permission) OR (permission LIKE :manage_permission))', 'AND', [
                'repair_permission' => '%can_repair%',
                'manage_permission' => '%can_manage_repair%'
            ]);
        }

        $options = [];
        foreach ($query->fetchAll() as $row) {
            $options[] = [
                'value' => (string) $row->id,
                'text' => (string) $row->name
            ];
        }

        return $options;
    }

    /**
     * Operator options limited to the current user.
     *
     * @param object|null $login
     *
     * @return array
     */
    public static function getCurrentOperatorOption($login): array
    {
        if (!$login || empty($login->id)) {
            return [];
        }

        $text = trim((string) ($login->name ?? ''));
        if ($text === '') {
            $text = trim((string) ($login->username ?? ''));
        }
        if ($text === '') {
            $text = (string) $login->id;
        }

        return [[
            'value' => (string) $login->id,
            'text' => $text
        ]];
    }

    /**
     * Get latest status rows keyed by repair ID.
     *
     * @param array $repairIds
     *
     * @return array<int, object>
     */
    public static function getLatestStatusMap(array $repairIds): array
    {
        $repairIds = array_values(array_unique(array_filter(array_map('intval', $repairIds))));
        if (empty($repairIds)) {
            return [];
        }

        $rows = \Kotchasan\Model::createQuery()
            ->select('S.id', 'S.repair_id', 'S.status', 'S.operator_id', 'S.comment', 'S.created_at', 'S.cost')
            ->from('repair_status S')
            ->where(['S.repair_id', $repairIds])
            ->orderBy('S.id', 'DESC')
            ->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $repairId = (int) $row->repair_id;
            if (!isset($map[$repairId])) {
                $map[$repairId] = $row;
            }
        }

        return $map;
    }

    /**
     * Build structured history rows for the repair status modal.
     *
     * @param int $repairId
     *
     * @return array
     */
    public static function getHistoryRows(int $repairId): array
    {
        if ($repairId <= 0) {
            return [];
        }

        $rows = \Kotchasan\Model::createQuery()
            ->select('S.id', 'S.status', 'S.comment', 'S.created_at', 'S.cost', 'U.name operator_name', 'M.name member_name')
            ->from('repair_status S')
            ->join('user U', ['U.id', 'S.operator_id'], 'LEFT')
            ->join('user M', ['M.id', 'S.member_id'], 'LEFT')
            ->where(['S.repair_id', $repairId])
            ->orderBy('S.id')
            ->fetchAll();

        $history = [];
        foreach ($rows as $row) {
            $comment = trim((string) ($row->comment ?? ''));
            $history[] = [
                'created_at' => (string) $row->created_at,
                'status_text' => self::getStatusText((int) $row->status),
                'operator_name' => (string) ($row->operator_name ?? ''),
                'member_name' => (string) ($row->member_name ?? ''),
                'cost_text' => (float) $row->cost > 0 ? number_format((float) $row->cost, 2) : '',
                'comment_text' => $comment === '' ? '' : str_replace(["\r\n", "\r", "\n"], ' / ', $comment)
            ];
        }

        return $history;
    }

    /**
     * Permission helper for repair managers.
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canManageRepair($login): bool
    {
        return ApiController::hasPermission($login, ['can_manage_repair', 'can_config']);
    }

    /**
     * Permission helper for repair processing.
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canProcessRepair($login): bool
    {
        return ApiController::hasPermission($login, ['can_manage_repair', 'can_repair', 'can_config']);
    }

    /**
     * View permission for a repair row.
     *
     * @param object|null $login
     * @param object $row
     *
     * @return bool
     */
    public static function canViewRepair($login, $row): bool
    {
        if (!$login || !$row) {
            return false;
        }

        if (self::canProcessRepair($login)) {
            return true;
        }

        return (int) $login->id === (int) $row->customer_id;
    }

    /**
     * Edit permission for requester or manager.
     *
     * @param object|null $login
     * @param object $row
     * @param int|null $latestStatusId
     *
     * @return bool
     */
    public static function canEditRequest($login, $row, ?int $latestStatusId): bool
    {
        if (!$login || !$row) {
            return false;
        }
        if (self::canManageRepair($login)) {
            return true;
        }
        if ((int) $login->id !== (int) $row->customer_id) {
            return false;
        }

        return $latestStatusId !== null && $latestStatusId === self::getFirstStatusId();
    }
}