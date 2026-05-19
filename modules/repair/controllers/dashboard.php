<?php
/**
 * @filesource modules/repair/controllers/dashboard.php
 */

namespace Repair\Dashboard;

use Kotchasan\Http\Request;

class Controller extends \Gcms\Api
{
    /**
     * Authenticate the current dashboard request.
     *
     * @param Request $request
     *
     * @return object
     */
    protected function getLogin(Request $request)
    {
        $login = $this->authenticateRequest($request);
        if (!$login) {
            throw new \RuntimeException('Unauthorized', 401);
        }

        return $login;
    }

    /**
     * Cards endpoint for the repair dashboard.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function cards(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            return $this->successResponse(Model::getCards($login), 'Dashboard cards retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Department graph endpoint for the repair dashboard.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function department(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            return $this->successResponse(Model::getDepartmentGraph($login), 'Department dashboard graph retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Operator graph endpoint for the repair dashboard.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function operators(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            return $this->successResponse(Model::getOperatorGraph($login), 'Operator dashboard graph retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}