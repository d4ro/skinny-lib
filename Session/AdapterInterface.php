<?php

namespace Skinny\Session;

/**
 * Description of AdapterInterface
 *
 * @author Daro
 */
interface AdapterInterface {

    function setSessionConfig($config);

    function open($savePath, $sessionName);

    function close();

    function read($id);

    function write($id, $data);

    function destroy($id);

    function gc($maxlifetime);
}
