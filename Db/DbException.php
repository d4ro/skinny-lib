<?php

namespace Skinny\Db;

/**
 * Description of Exception
 *
 * @author Daro
 */
class DbException extends \Skinny\Exception {
    // TODO: wszystkie wyjątki PDO mają być łapane i przekazywane jako $previous do tej klasy wraz z zapytaniem SQL, który spowodował wyjątek.
    // A także wszystkie wyjątki generowane przez Skinny\Db powinny być instacją tej klasy (także z odpowiadającym SQL).
}
