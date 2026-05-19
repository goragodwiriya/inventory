<?php
/**
 * @filesource modules/inventory/controllers/items.php
 */

namespace Inventory\Items;

use Gcms\Api as ApiController;
use Inventory\Assignment\Model as AssignmentModel;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Load item-row editor data.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, ['can_manage_inventory', 'can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $data = Model::get($request->get('id')->toInt());
            if ($data === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse($data, 'Inventory item rows retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save item rows for an asset.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login, ['can_manage_inventory', 'can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id')->toInt();
            $asset = \Inventory\Asset\Model::getRecord($id);
            if ($asset === null) {
                return $this->errorResponse('No data available', 404);
            }

            $productNos = $request->post('product_no', [])->topic();
            $units = $request->post('unit', [])->topic();
            $stocks = $request->post('stock', [])->toDouble();

            $errors = [];
            $rows = [];
            $seen = [];
            $submittedProductNos = [];

            foreach ($productNos as $rowKey => $productNo) {
                $productNo = trim((string) $productNo);
                $unit = trim((string) ($units[$rowKey] ?? ''));
                $stock = max(0, (float) ($stocks[$rowKey] ?? 0));

                if ($productNo === '' && $unit === '' && $stock <= 0) {
                    continue;
                }

                if ($productNo === '') {
                    $errors['inventoryItems_product_no_'.$rowKey] = 'Please fill in';
                } elseif (isset($seen[$productNo])) {
                    $errors['inventoryItems_product_no_'.$rowKey] = 'This serial number already exists';
                } elseif (\Inventory\Asset\Model::findDuplicateProductNo($productNo, $id) !== null) {
                    $errors['inventoryItems_product_no_'.$rowKey] = 'This serial number already exists';
                } else {
                    $seen[$productNo] = true;
                    $submittedProductNos[] = $productNo;
                }

                if ($unit === '') {
                    $errors['inventoryItems_unit_'.$rowKey] = 'Please select';
                }

                if ($stock <= 0) {
                    $errors['inventoryItems_stock_'.$rowKey] = 'Please fill in';
                }

                $rows[] = [
                    'row_key' => $rowKey,
                    'product_no' => $productNo,
                    'unit' => $unit,
                    'stock' => $stock
                ];
            }

            if (empty($rows)) {
                return $this->errorResponse('Please add at least one item row', 400);
            }

            $blockedRemovals = array_diff(Model::getRepairLinkedProductNumbers($id), $submittedProductNos);
            if (!empty($blockedRemovals)) {
                return $this->errorResponse('Some serial numbers are referenced by repair records and cannot be removed', 400);
            }

            $blockedAssignedRemovals = array_diff(AssignmentModel::getActiveProductNosByInventoryId($id), $submittedProductNos);
            if (!empty($blockedAssignedRemovals)) {
                return $this->errorResponse('Some item rows still have active holder assignments and cannot be removed', 400);
            }

            $currentQuantities = AssignmentModel::getCurrentQuantities($submittedProductNos);
            foreach ($rows as $row) {
                if ($row['product_no'] !== '' && isset($currentQuantities[$row['product_no']]) && $row['stock'] < $currentQuantities[$row['product_no']]) {
                    $errors['inventoryItems_stock_'.$row['row_key']] = 'Stock cannot be less than the currently assigned quantity';
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $rows = array_map(static function ($row) {
                unset($row['row_key']);

                return $row;
            }, $rows);

            Model::replace($id, $rows);

            \Index\Log\Model::add($id, 'inventory', 'Save', 'Saved inventory item rows: '.$asset->topic, $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 800);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}