<?php
/**
 * @filesource modules/repair/models/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=repair-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id ID
     * @param string $product_no
     *
     * @return object|null
     */
    public static function get($id, $product_no = '')
    {
        if (empty($id)) {
            // ใหม่
            if ($product_no == '') {
                return (object) array(
                    'id' => 0,
                    'product_no' => '',
                    'topic' => '',
                    'job_description' => '',
                    'comment' => '',
                    'status_id' => 0
                );
            } else {
                return static::createQuery()
                    ->from('inventory_items I')
                    ->where(array('I.product_no', $product_no))
                    ->first('0 id', 'I.product_no');
            }
        } else {
            // แก้ไข
            return static::createQuery()
                ->from('repair R')
                ->join('inventory_items I', 'LEFT', array('I.product_no', 'R.product_no'))
                ->join('inventory V', 'LEFT', array('V.id', 'I.inventory_id'))
                ->where(array('R.id', $id))
                ->first('R.*', 'V.topic');
        }
    }

    /**
     * บันทึกค่าจากฟอร์ม (receive.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // รับค่าจากการ POST
                $repair = array(
                    'job_description' => $request->post('job_description')->textarea(),
                    'appraiser' => 0
                );
                $product_no = $request->post('product_no')->topic();
                if (empty($product_no)) {
                    // ไม่พบรายการพัสดุที่เลือก
                    $ret['ret_product_no'] = Language::get('Please select from the search results');
                }
                if (empty($ret)) {
                    // สามารถจัดการรายการซ่อมได้
                    $can_manage_repair = Login::checkPermission($login, 'can_manage_repair');
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt(), $product_no);
                    if ($index && ($index->id == 0 || $login['id'] == $index->customer_id || $can_manage_repair)) {
                        // ตาราง
                        $repair_table = $this->getTableName('repair');
                        $repair_status_table = $this->getTableName('repair_status');
                        // Database
                        $db = $this->db();
                        // product no
                        $repair['product_no'] = $index->product_no;
                        if ($index->id == 0) {
                            // ใหม่
                            $repair['customer_id'] = $login['id'];
                            $repair['create_date'] = date('Y-m-d H:i:s');
                            // job_id
                            $repair['job_id'] = \Index\Number\Model::get(0, 'repair_job_no', $repair_table, 'job_id', self::$cfg->repair_prefix);
                            // บันทึกรายการแจ้งซ่อม รายการแรก
                            $log = array(
                                'repair_id' => $db->insert($repair_table, $repair),
                                'member_id' => $login['id'],
                                'comment' => $request->post('comment')->topic(),
                                'status' => isset(self::$cfg->repair_first_status) ? self::$cfg->repair_first_status : 1,
                                'create_date' => $repair['create_date'],
                                'operator_id' => 0
                            );
                            $db->insert($repair_status_table, $log);
                            // ใหม่ ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                            $ret['alert'] = \Repair\Email\Model::send($log['repair_id']);
                        } else {
                            // แก้ไขรายการแจ้งซ่อม
                            $db->update($repair_table, $index->id, $repair);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                        }
                        if ($can_manage_repair && $index->id > 0) {
                            // สามารถจัดการรายการซ่อมได้
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'repair-setup', 'id' => null));
                        } else {
                            // ใหม่
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'repair-history', 'id' => null));
                        }
                        // clear
                        $request->removeToken();
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
