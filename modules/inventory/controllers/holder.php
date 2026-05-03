<?php
/**
 * @filesource modules/inventory/controllers/holder.php
 */

namespace Inventory\Holder;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Load holder modal data.
     * GET ?product_no=<serial>&modal=1  — edit existing item
     * GET ?modal=1                      — select an existing item
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

            $productNo = $request->get('product_no', '')->topic();
            $response = Model::buildModalResponse($productNo);

            if ($response === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            if ($request->get('modal')->toBoolean()) {
                $isEdit = $productNo !== '';
                $response['actions'] = [
                    [
                        'type' => 'modal',
                        'action' => 'show',
                        'template' => '/inventory/holder.html',
                        'title' => $isEdit ? '{LNG_Edit} {LNG_Holder}' : '{LNG_Add} {LNG_Holder}',
                        'titleClass' => 'icon-user'
                    ]
                ];
            }

            return $this->successResponse($response, 'Holder data retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save holder assignment for an existing item.
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

            $originalProductNo = $request->post('original_product_no', '')->topic();
            $productNo = $request->post('product_no')->topic();
            $holderIdStr = trim($request->post('holder_id')->toString());
            $quantity = max(0, $request->post('quantity')->toDouble());

            $errors = [];
            $selectedProductNo = $originalProductNo !== '' ? $originalProductNo : $productNo;
            $isAdd = $originalProductNo === '';
            $current = null;

            if ($selectedProductNo === '') {
                $errors['product_no'] = 'Please select';
            } else {
                $current = Model::get($selectedProductNo);
                if ($current === null) {
                    $errors['product_no'] = 'Please select from the search results';
                }
            }

            if ($isAdd && $holderIdStr === '') {
                $errors['holder_id'] = 'Please select from the search results';
            }

            if ($holderIdStr !== '' && !ctype_digit($holderIdStr)) {
                $errors['holder_id'] = 'Please select from the search results';
            }

            if ($holderIdStr !== '' && $current !== null) {
                if ($quantity <= 0) {
                    $errors['quantity'] = 'Please fill in';
                } elseif ($quantity > (float) $current->stock) {
                    $errors['quantity'] = 'Assigned quantity cannot be greater than stock';
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $saved = Model::save($selectedProductNo, $holderIdStr, $quantity);
            $action = $isAdd ? 'Add' : 'Edit';
            \Index\Log\Model::add(0, 'inventory', $action, ($isAdd ? 'Created holder assignment for existing item: ' : 'Updated holder assignment for item: ').$saved, $login->id);

            return $this->successResponse([
                'actions' => [
                    [
                        'type' => 'notification',
                        'level' => 'success',
                        'message' => 'Saved successfully'
                    ],
                    [
                        'type' => 'redirect',
                        'url' => 'reload',
                        'target' => 'table',
                        'delay' => 800
                    ],
                    [
                        'type' => 'modal',
                        'action' => 'close'
                    ]
                ]
            ], 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
