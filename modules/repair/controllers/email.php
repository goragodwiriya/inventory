<?php
/**
 * @filesource modules/repair/controllers/email.php
 */

namespace Repair\Email;

use Kotchasan\Language;

class Controller extends \Kotchasan\KBase
{
    /**
     * Send repair notifications from request ID.
     *
     * @param int $id
     *
     * @return string
     */
    public static function sendByRequestId(int $id): string
    {
        $order = \Kotchasan\Model::createQuery()
            ->select(
                'R.id',
                'R.customer_id',
                'R.job_id',
                'R.product_no',
                'R.job_description',
                'R.appointment_date',
                'R.informer',
                'R.appraiser',
                'R.created_at',
                'U.name customer_name',
                'V.topic'
            )
            ->from('repair R')
            ->join('user U', ['U.id', 'R.customer_id'], 'LEFT')
            ->join('inventory_items I', ['I.product_no', 'R.product_no'], 'LEFT')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->where(['R.id', $id])
            ->first();

        if (!$order) {
            return Language::get('Saved successfully');
        }

        $statusRows = \Kotchasan\Model::createQuery()
            ->select('S.id', 'S.status', 'S.operator_id', 'S.comment', 'S.created_at', 'S.cost', 'O.name operator_name')
            ->from('repair_status S')
            ->join('user O', ['O.id', 'S.operator_id'], 'LEFT')
            ->where(['S.repair_id', $id])
            ->orderBy('S.id', 'DESC')
            ->fetchAll();

        $latest = $statusRows[0] ?? null;

        return self::send([
            'id' => (int) $order->id,
            'customer_id' => (int) $order->customer_id,
            'job_id' => (string) $order->job_id,
            'product_no' => (string) $order->product_no,
            'job_description' => (string) $order->job_description,
            'appointment_date' => (string) $order->appointment_date,
            'created_at' => (string) $order->created_at,
            'customer_name' => (string) $order->customer_name,
            'informer' => (string) ($order->informer ?? ''),
            'topic' => (string) $order->topic,
            'status' => $latest ? (int) $latest->status : \Repair\Helper\Controller::getFirstStatusId(),
            'status_text' => $latest ? \Repair\Helper\Controller::getStatusText((int) $latest->status) : '',
            'operator_id' => $latest ? (int) $latest->operator_id : 0,
            'operator_name' => $latest ? (string) ($latest->operator_name ?? '') : '',
            'comment' => $latest ? (string) ($latest->comment ?? '') : '',
            'cost' => $latest ? (float) ($latest->cost ?? 0) : 0,
            'is_new_request' => count($statusRows) <= 1
        ]);
    }

    /**
     * Send email and chat notifications.
     *
     * @param array $order
     *
     * @return string
     */
    private static function send(array $order): string
    {
        $lines = [];
        $emails = [];
        $telegrams = [];
        if (!empty(self::$cfg->telegram_chat_id)) {
            $telegrams[self::$cfg->telegram_chat_id] = self::$cfg->telegram_chat_id;
        }

        $name = '';
        $mailto = '';
        $lineUid = '';
        $telegramId = '';

        if (self::$cfg->demo_mode) {
            $where = [
                ['U.id', [$order['customer_id'], 1]]
            ];
        } else {
            $where = [
                ['U.id', $order['customer_id']],
                ['U.status', 1]
            ];
        }

        $query = \Kotchasan\Model::createQuery()
            ->select('U.id', 'U.username', 'U.name', 'U.line_uid', 'U.telegram_id')
            ->from('user U')
            ->where(['U.active', 1])
            ->where($where, 'OR')
            ->groupBy('U.id')
            ->cacheOn();

        if (!self::$cfg->demo_mode) {
            $query->whereRaw('(`U`.`permission` LIKE :repair_permission OR `U`.`permission` LIKE :manage_permission)', 'OR', [
                'repair_permission' => '%can_repair%',
                'manage_permission' => '%can_manage_repair%'
            ]);
        }

        foreach ($query->fetchAll() as $item) {
            if ((int) $item->id === (int) $order['customer_id']) {
                $name = (string) $item->name;
                $mailto = (string) $item->username;
                $lineUid = (string) $item->line_uid;
                $telegramId = (string) $item->telegram_id;
                if ($order['customer_name'] === '') {
                    $order['customer_name'] = (string) $item->name;
                }
            } else {
                $emails[$item->username] = $item->name.'<'.$item->username.'>';
                if (!empty($item->line_uid)) {
                    $lines[$item->line_uid] = $item->line_uid;
                }
                if (!empty($item->telegram_id)) {
                    $telegrams[$item->telegram_id] = $item->telegram_id;
                }
            }
        }

        $viewClass = '\\Repair\\View\\View';
        $msg = Language::trans(class_exists($viewClass) ? $viewClass::render($order, true) : '');
        $ret = [];

        if (!empty(self::$cfg->telegram_bot_token)) {
            $err = \Gcms\Telegram::sendTo($telegrams, $msg);
            if ($err !== '') {
                $ret[] = $err;
            }
            $err = \Gcms\Telegram::sendTo($telegramId, $msg);
            if ($err !== '') {
                $ret[] = $err;
            }
        }

        if (!empty(self::$cfg->line_channel_access_token)) {
            $err = \Gcms\Line::sendTo($lines, $msg);
            if ($err !== '') {
                $ret[] = $err;
            }
            $err = \Gcms\Line::sendTo($lineUid, $msg);
            if ($err !== '') {
                $ret[] = $err;
            }
        }

        if (self::$cfg->noreply_email != '') {
            $subject = '['.self::$cfg->web_title.'] '.Language::get($order['is_new_request'] ? 'Get a repair' : 'Update repair status').' '.$order['job_id'];
            if ($mailto !== '') {
                $err = \Kotchasan\Email::send($name.'<'.$mailto.'>', self::$cfg->noreply_email, $subject, $msg);
                if ($err->error()) {
                    $ret[] = strip_tags($err->getErrorMessage());
                }
            }
            foreach ($emails as $item) {
                $err = \Kotchasan\Email::send($item, self::$cfg->noreply_email, $subject, $msg);
                if ($err->error()) {
                    $ret[] = strip_tags($err->getErrorMessage());
                }
            }
        }

        if (isset($err)) {
            return empty($ret) ? Language::get('Your message was sent successfully') : implode("\n", array_unique($ret));
        }

        return Language::get('Saved successfully');
    }
}