<?php
/**
 * @filesource modules/repair/models/dashboard.php
 */

namespace Repair\Dashboard;

use Kotchasan\Database\Sql;

class Model extends \Kotchasan\Model
{
    /**
     * Get the dashboard card payload.
     *
     * @param object $login
     *
     * @return array
     */
    public static function getCards($login): array
    {
        $canProcessRepair = \Repair\Helper\Controller::canProcessRepair($login);
        $rows = self::getLatestRows($canProcessRepair ? null : (int) $login->id);
        $statusCounts = self::countRowsByStatus($rows);
        $statusRows = self::normalizeStatusRows(\Repair\Helper\Controller::getStatusRows(), $statusCounts);

        return [
            'show_assigned_cards' => $canProcessRepair,
            'show_queue_cards' => $canProcessRepair,
            'show_department_chart' => $canProcessRepair,
            'show_operator_chart' => $canProcessRepair,
            'my_status_cards' => self::buildStatusCards(
                self::countRowsByStatus(self::filterRowsByCustomer($rows, (int) $login->id)),
                $statusRows,
                '/repair-history',
                '{LNG_My requests}',
                'icon-list'
            ),
            'assigned_status_cards' => $canProcessRepair
                ? self::buildStatusCards(
                self::countRowsByStatus(self::filterRowsByOperator($rows, (int) $login->id)),
                $statusRows,
                '/repair-jobs',
                '{LNG_Assigned to you}',
                'icon-tools',
                ['operator_id' => (int) $login->id]
            )
                : [],
            'queue_status_cards' => $canProcessRepair
                ? self::buildStatusCards(
                $statusCounts,
                $statusRows,
                '/repair-jobs',
                '{LNG_All repair jobs}',
                'icon-dashboard'
            )
                : []
        ];
    }

    /**
     * Build department graph data.
     *
     * @param object $login
     *
     * @return array
     */
    public static function getDepartmentGraph($login): array
    {
        if (!\Repair\Helper\Controller::canProcessRepair($login)) {
            return [
                'series' => [],
                'total_departments' => 0
            ];
        }

        return self::buildGroupedStatusGraph(self::getLatestRows(), 'department');
    }

    /**
     * Build operator graph data.
     *
     * @param object $login
     *
     * @return array
     */
    public static function getOperatorGraph($login): array
    {
        if (!\Repair\Helper\Controller::canProcessRepair($login)) {
            return [
                'series' => [],
                'total_operators' => 0
            ];
        }

        return self::buildGroupedStatusGraph(self::getLatestRows(), 'operator');
    }

    /**
     * Load repair rows with the latest status, requester department, and operator context.
     *
     * @param int|null $customerId
     *
     * @return array
     */
    protected static function getLatestRows(?int $customerId = null): array
    {
        $q1 = static::createQuery()
            ->select('id')
            ->from('repair_status')
            ->where(['repair_id', Sql::column('R.id')])
            ->orderBy('id', 'DESC')
            ->limit(1);

        $query = static::createQuery()
            ->select(
                'R.id',
                'R.customer_id',
                'S.status',
                'S.operator_id',
                'RequesterDepartment.value department_id',
                'Department.topic department_name',
                'Operator.name operator_name',
                'Operator.permission operator_permission'
            )
            ->from('repair R')
            ->join('repair_status S', ['S.id', $q1], 'LEFT')
            ->join('user_meta RequesterDepartment', [['RequesterDepartment.member_id', 'R.customer_id'], ['RequesterDepartment.name', 'department']], 'LEFT')
            ->join('category Department', [['Department.category_id', Sql::column('RequesterDepartment.value')], ['Department.type', 'department']], 'LEFT')
            ->join('user Operator', ['Operator.id', 'S.operator_id'], 'LEFT');

        if ($customerId !== null && $customerId > 0) {
            $query->where(['R.customer_id', $customerId]);
        }

        return $query->fetchAll();
    }

    /**
     * Count repairs by latest status.
     *
     * @param array $rows
     *
     * @return array<int, int>
     */
    protected static function countRowsByStatus(array $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            if (!isset($row->status) || $row->status === '' || $row->status === null) {
                continue;
            }

            $statusId = (int) $row->status;
            $counts[$statusId] = ($counts[$statusId] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Ensure all counted statuses exist in the status metadata array.
     *
     * @param array $statusRows
     * @param array $counts
     *
     * @return array
     */
    protected static function normalizeStatusRows(array $statusRows, array $counts): array
    {
        $known = [];
        foreach ($statusRows as $row) {
            $known[(int) ($row['value'] ?? 0)] = true;
        }

        foreach (array_keys($counts) as $statusId) {
            if (!isset($known[$statusId])) {
                $statusRows[] = [
                    'value' => (string) $statusId,
                    'text' => \Repair\Helper\Controller::getStatusText($statusId),
                    'color' => ''
                ];
            }
        }

        usort($statusRows, static function (array $first, array $second): int {
            return (int) ($first['value'] ?? 0) <=> (int) ($second['value'] ?? 0);
        });

        return $statusRows;
    }

    /**
     * Build a set of status cards for the dashboard.
     *
     * @param array $counts
     * @param array $statusRows
     * @param string $baseUrl
     * @param string $scopeText
     * @param string $iconClass
     * @param array $extraQuery
     *
     * @return array
     */
    protected static function buildStatusCards(array $counts, array $statusRows, string $baseUrl, string $scopeText, string $iconClass, array $extraQuery = []): array
    {
        $cards = [];

        foreach ($statusRows as $statusRow) {
            $statusId = (int) ($statusRow['value'] ?? 0);
            $count = (int) ($counts[$statusId] ?? 0);
            $cards[] = [
                'status_id' => (string) $statusId,
                'label' => (string) ($statusRow['text'] ?? ''),
                'count_text' => number_format($count),
                'scope_text' => $scopeText,
                'href' => self::buildFilterUrl($baseUrl, array_merge($extraQuery, ['status' => $statusId])),
                'color' => trim((string) ($statusRow['color'] ?? '')) !== '' ? (string) $statusRow['color'] : 'var(--color-primary)',
                'icon_class' => $iconClass
            ];
        }

        return $cards;
    }

    /**
     * Build grouped graph series by status.
     *
     * @param array $rows
     * @param string $dimension
     *
     * @return array
     */
    protected static function buildGroupedStatusGraph(array $rows, string $dimension): array
    {
        $statusCounts = self::countRowsByStatus($rows);
        $statusRows = self::normalizeStatusRows(\Repair\Helper\Controller::getStatusRows(), $statusCounts);
        $groups = [];

        foreach ($rows as $row) {
            if (!isset($row->status) || $row->status === '' || $row->status === null) {
                continue;
            }

            [$groupKey, $groupLabel] = self::resolveGroup($row, $dimension);
            $statusId = (int) $row->status;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'label' => $groupLabel,
                    'total' => 0,
                    'status_counts' => []
                ];
            }

            $groups[$groupKey]['status_counts'][$statusId] = ($groups[$groupKey]['status_counts'][$statusId] ?? 0) + 1;
            ++$groups[$groupKey]['total'];
        }

        uasort($groups, static function (array $first, array $second): int {
            return $second['total'] <=> $first['total'];
        });

        $series = [];
        foreach ($statusRows as $statusRow) {
            $statusId = (int) ($statusRow['value'] ?? 0);
            $points = [];
            $hasValue = false;

            foreach ($groups as $group) {
                $value = (int) ($group['status_counts'][$statusId] ?? 0);
                if ($value > 0) {
                    $hasValue = true;
                }
                $points[] = [
                    'label' => $group['label'],
                    'value' => $value
                ];
            }

            if ($hasValue) {
                $series[] = [
                    'name' => (string) ($statusRow['text'] ?? $statusId),
                    'data' => $points
                ];
            }
        }

        $payload = [
            'series' => $series
        ];

        if ($dimension === 'department') {
            $payload['total_departments'] = count($groups);
        } else {
            $payload['total_operators'] = count($groups);
        }

        return $payload;
    }

    /**
     * Resolve grouping key and label for graphs.
     *
     * @param object $row
     * @param string $dimension
     *
     * @return array
     */
    protected static function resolveGroup($row, string $dimension): array
    {
        if ($dimension === 'department') {
            $groupKey = trim((string) ($row->department_id ?? ''));
            $groupLabel = trim((string) ($row->department_name ?? ''));

            if ($groupKey === '') {
                $groupKey = 'not_specified';
            }
            if ($groupLabel === '') {
                $groupLabel = '{LNG_Not specified}';
            }

            return [$groupKey, $groupLabel];
        }

        $operatorId = (int) ($row->operator_id ?? 0);
        $operatorName = trim((string) ($row->operator_name ?? ''));
        $permission = (string) ($row->operator_permission ?? '');

        if ($operatorId > 0 && $operatorName !== '' && self::isRepairStaff($permission)) {
            return ['operator_'.$operatorId, $operatorName];
        }

        return ['unassigned', '{LNG_Unassigned}'];
    }

    /**
     * Filter rows for the current requester.
     *
     * @param array $rows
     * @param int $customerId
     *
     * @return array
     */
    protected static function filterRowsByCustomer(array $rows, int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        return array_values(array_filter($rows, static function ($row) use ($customerId): bool {
            return (int) ($row->customer_id ?? 0) === $customerId;
        }));
    }

    /**
     * Filter rows by the current operator.
     *
     * @param array $rows
     * @param int $operatorId
     *
     * @return array
     */
    protected static function filterRowsByOperator(array $rows, int $operatorId): array
    {
        if ($operatorId <= 0) {
            return [];
        }

        return array_values(array_filter($rows, static function ($row) use ($operatorId): bool {
            return (int) ($row->operator_id ?? 0) === $operatorId;
        }));
    }

    /**
     * Build a filtered dashboard URL.
     *
     * @param string $baseUrl
     * @param array $query
     *
     * @return string
     */
    protected static function buildFilterUrl(string $baseUrl, array $query): string
    {
        $query = array_filter($query, static function ($value): bool {
            return $value !== '' && $value !== null;
        });

        if (empty($query)) {
            return $baseUrl;
        }

        return $baseUrl.(strpos($baseUrl, '?') === false ? '?' : '&').http_build_query($query);
    }

    /**
     * Check whether the permission string belongs to repair staff.
     *
     * @param string $permission
     *
     * @return bool
     */
    protected static function isRepairStaff(string $permission): bool
    {
        return strpos($permission, 'can_repair') !== false || strpos($permission, 'can_manage_repair') !== false;
    }
}