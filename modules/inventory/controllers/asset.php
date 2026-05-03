<?php
/**
 * @filesource modules/inventory/controllers/asset.php
 */

namespace Inventory\Asset;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Load asset form data.
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

            $data = Model::get($request->get('id', 0)->toInt());
            if ($data === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $categories = \Inventory\Category\Controller::init();

            $response = [
                'data' => $data,
                'options' => [
                    'category_id' => $categories->toOptions('category_id', true, null, ['' => '{LNG_Please select}']),
                    'type_id' => $categories->toOptions('type_id', true, null, ['' => '{LNG_Please select}']),
                    'model_id' => $categories->toOptions('model_id', true, null, ['' => '{LNG_Please select}']),
                    'unit' => $categories->toOptions('unit', false, null, ['' => '{LNG_Please select}']),
                    'holder_id' => Model::getHolderOptions()
                ]
            ];

            if ($request->get('modal')->toBoolean()) {
                $response['actions'] = [
                    [
                        'type' => 'modal',
                        'action' => 'show',
                        'template' => '/inventory/asset.html',
                        'title' => ($data->id > 0 ? '{LNG_Edit} {LNG_Inventory}' : '{LNG_Add} {LNG_Inventory}'),
                        'titleClass' => 'icon-product'
                    ]
                ];
            }

            return $this->successResponse($response, 'Inventory details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save asset details.
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

            $id = $request->post('id', 0)->toInt();
            if ($id > 0 && Model::getRecord($id) === null) {
                return $this->errorResponse('No data available', 404);
            }

            $save = [
                'topic' => $request->post('topic')->topic(),
                'category_id' => $request->post('category_id')->topic(),
                'type_id' => $request->post('type_id')->topic(),
                'model_id' => $request->post('model_id')->topic(),
                'inuse' => $request->post('inuse')->toBoolean() ? 1 : 0
            ];
            $meta = [
                'detail' => $request->post('detail')->textarea(),
                'location' => $request->post('location')->topic()
            ];
            $holderId = trim($request->post('holder_id')->toString());
            $item = [
                'product_no' => $request->post('product_no')->topic(),
                'unit' => $request->post('unit')->topic(),
                'stock' => max(0, $request->post('stock')->toDouble()),
                'holder_id' => ($id === 0 && ctype_digit($holderId)) ? $holderId : ''
            ];

            $errors = [];
            if ($save['topic'] === '') {
                $errors['topic'] = 'Please fill in';
            }
            if ($id === 0 && $item['product_no'] === '') {
                $errors['product_no'] = 'Please fill in';
            }
            if ($id === 0 && $item['unit'] === '') {
                $errors['unit'] = 'Please select';
            }
            if ($id === 0 && $item['stock'] <= 0) {
                $errors['stock'] = 'Please fill in';
            }
            if ($id === 0 && $holderId !== '' && !ctype_digit($holderId)) {
                $errors['holder_id'] = 'Please select from the search results';
            }

            $duplicate = Model::findDuplicateProductNo($item['product_no'], $id);
            if ($id === 0 && $duplicate) {
                $errors['product_no'] = 'This serial number already exists';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $id = Model::save($id, $save, $meta, $item);

            $ret = [];
            \Download\Upload\Model::execute(
                $ret,
                $request,
                $id,
                'inventory',
                self::$cfg->img_typies,
                0,
                (int) (self::$cfg->inventory_w ?? 800)
            );

            \Index\Log\Model::add($id, 'inventory', 'Save', 'Saved inventory asset: '.$save['topic'], $login->id);

            $isModal = $request->post('modal')->toBoolean();
            $message = empty($ret) ? 'Saved successfully' : ($ret['inventory'] ?? 'Upload failed');
            $actions = [
                [
                    'type' => 'notification',
                    'level' => empty($ret) ? 'success' : 'error',
                    'message' => $message
                ]
            ];

            if ($isModal) {
                $actions[] = [
                    'type' => 'redirect',
                    'url' => 'reload',
                    'target' => 'table',
                    'delay' => 800
                ];
                $actions[] = [
                    'type' => 'modal',
                    'action' => 'close'
                ];
            } else {
                $actions[] = [
                    'type' => 'redirect',
                    'url' => 'reload',
                    'delay' => 800
                ];
            }

            return $this->successResponse([
                'actions' => $actions
            ], $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Remove uploaded image.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function removeImage(Request $request)
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

            $json = json_decode($request->post('id')->toString());
            if (!$json || !isset($json->id, $json->file)) {
                return $this->errorResponse('No data available', 404);
            }
            if (Model::getRecord((int) $json->id) === null) {
                return $this->errorResponse('No data available', 404);
            }

            $file = ROOT_PATH.DATA_FOLDER.'inventory/'.$json->id.'/'.$json->file;
            if (!is_file($file)) {
                return $this->errorResponse('No data available', 404);
            }

            @unlink($file);

            \Index\Log\Model::add((int) $json->id, 'inventory', 'Delete', 'Removed inventory image: '.$json->file, $login->id);

            return $this->successResponse([], 'Image removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}