<?php
use Hook\Http\Header;
use Yaf\Dispatcher;

abstract class AbstractController extends Yaf\Controller_Abstract
{
    public $result = [];
    public $definition = [];

    public function init()
    {
        if ($this->_request->module === 'Api') {
            Dispatcher::getInstance()->autoRender(false);
            $this->_request->setParam('version', $this->_request->action)->setActionName($this->_request->method);
            return false;
        }

        $viewPath = '';
        if ($this->_request->module !== 'Index') {
            $viewPath = 'modules/'.$this->_request->module;
        }
        $this->_view->setScriptPath(APP_ROOT.'/'.$viewPath.'/views/'.APP_THEME.'/');

        $this->_view->assign(['title' => l('application.title'), 'keywords' => l('application.keywords'), 'description' => l('application.description')]);

        if (!isset($_SESSION[APP_NAME])) {
            if ($this->_request->controller !== 'Login') {
                $this->forward('Login', 'index', ['referer' => $this->_request->getServer('REQUEST_URI', APP_CONFIG['http']['uri'])]);
            }
            return false;
        }

        if ($_SESSION[APP_NAME]['security']['ip'] !== $this->_request->getServer('REMOTE_ADDR') || $_SESSION[APP_NAME]['security']['agent'] !== $this->_request->getServer('HTTP_USER_AGENT')) {
            throw new Exception(l('security.hijack'));
        }

        if (!$this->_request->isGet() && $_SESSION[APP_NAME]['security']['token'] !== $this->_request->getPost('token')) {
            throw new Exception(l('security.csrf'));
        }

        $this->_view->assign(['menus' => MenuModel::classify()]);

        if (!isset($this->definition[$this->_request->action])) {
            return false;
        }
        foreach ($this->definition[$this->_request->action] as $field => $filter) {
            $result = filter_input($filter['type'], $field, $filter['filter'], $filter['options']);
            if ($result === false || $result === null) {
                throw new \InvalidArgumentException(l(get_called_class().'.'.$field.'.validate.error'));
            }

            $this->result[$field] = $result;
        }
    }

    public function send($data = [], int $status = 200, int $code = 10000, string $msg = '')
    {
        Header::setCharset();
        Header::setStatus($status);
        echo json_encode(['id' => mt_rand(), 'code' => $code, 'msg' => $msg, 'data' => $data]);
    }
}