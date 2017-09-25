<?php

namespace Depage\Notifications;

/**
 * brief Notfication
 * Class Notfication
 *
 * @todo add option for sms gateway?
 */
class Notification extends \Depage\Entity\PdoEntity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "id" => null,
        "uid" => null,
        "sid" => null,
        "tag" => "",
        "title" => "",
        "message" => "",
        "options" => "",
        "delivery" => ",notification,",
        "date" => null,
    );

    /**
     * @brief primary
     **/
    static protected $primary = array("id");

    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;
    // }}}

    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      void
     */
    public function __construct(\Depage\Db\Pdo $pdo) {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    // }}}

    // {{{ loadById()
    /**
     * gets a notifications by sid for a specific user
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       String            $sid        sid of the user
     * @param       String            $tag        tag with which to filter the notifications. SQL wildcards % and _ are allowed to match substrings.
     * @param       String            $delivery   delivery method that notification has to have
     *
     * @return      auth_user
     */
    static public function loadById($pdo, $id)
    {
        return self::loadBy($pdo, [
            'id' => $id,
        ]);
    }
    // }}}
    // {{{ loadBySid()
    /**
     * gets a notifications by sid for a specific user
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       String            $sid        sid of the user
     * @param       String            $tag        tag with which to filter the notifications. SQL wildcards % and _ are allowed to match substrings.
     * @param       String            $delivery   delivery method that notification has to have
     *
     * @return      auth_user
     */
    static public function loadBySid($pdo, $sid, $tag = null, $delivery = null) {
        return self::loadBy($pdo, [
            'sid' => $sid,
            'tag' => $tag,
            'delivery' => $delivery,
        ]);
    }
    // }}}
    // {{{ loadByTag()
    /**
     * gets a notifications by tag for all users
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       String            $tag        tag with which to filter the notifications. SQL wildcards % and _ are allowed to match substrings.
     */
    static public function loadByTag($pdo, $tag, $delivery = null) {
        return self::loadBy($pdo, [
            'tag' => $tag,
            'delivery' => $delivery,
        ]);
    }
    // }}}
    // {{{ loadByDelivery()
    /**
     * gets a notifications by delivery for all users
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       String            $tag        tag with which to filter the notifications. SQL wildcards % and _ are allowed to match substrings.
     */
    static public function loadByDelivery($pdo, $delivery) {
        return self::loadBy($pdo, [
            'delivery' => $delivery,
        ]);
    }
    // }}}
    // {{{ loadBy()
    /**
     * @brief loadBy
     *
     * @param mixed $param
     * @return void
     **/
    static public function loadBy($pdo, Array $search, Array $order = [])
    {
        $notifications = [];
        $fields = "n." . implode(", n.", self::getFields());
        $where = [];
        $params = [];
        $groupBy = "";
        $orderBy = "";
        $join = "";

        // extract where part of query
        if (isset($search['id'])) {
            $where[] = self::sqlConditionFor('n.id', $search['id'], $params);
        }
        if (isset($search['sid'])) {
            $where[] = "(n.sid = :sid1 OR
                (s.sid = :sid2 AND n.uid = s.userId))";
            $params["sid1"] = $search['sid'];
            $params["sid2"] = $search['sid'];
            $join .= "LEFT JOIN {$pdo->prefix}_auth_sessions AS s ON n.uid = s.userId";
        }
        if (!empty($search['tag'])) {
            $where[] = "n.tag LIKE :tag";
            $params["tag"] = $search['tag'];
        }
        if (!empty($search['delivery'])) {
            $where[] = "delivery LIKE CONCAT('%,', :delivery, ',%')";
            $params['delivery'] = $search['delivery'];
        }

        if (!empty($where)) {
            $where = "WHERE " . implode($where, " AND ");
        } else {
            $where = "";
        };

        // extract order part of query
        if (!empty($order)) {
            $orderBy = "ORDER BY " . implode(", ", $order);
        }

        $sql =
            "SELECT $fields
            FROM
                {$pdo->prefix}_notifications AS n
                $join
            $where
            $groupBy
            $orderBy";
        $query = $pdo->prepare($sql);
        $query->execute($params);

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, get_called_class(), [$pdo]);

        do {
            $notification = $query->fetch(\PDO::FETCH_CLASS);
            if ($notification) {
                $notification->onLoad();
                $notifications[] = $notification;
            }
        } while ($notification);

        return $notifications;
    }
    // }}}

    // {{{ setOptions()
    /**
     * @brief setOptions
     *
     * @param mixed $param
     * @return void
     **/
    public function setOptions($param)
    {
        if (!$this->initialized) {
            $this->data['options'] = $param;
        } else {
            $this->data['options'] = serialize($param);
            $this->dirty['options'] = true;
        }
    }
    // }}}
    // {{{ getOptions()
    /**
     * @brief getOptions
     *
     * @param mixed
     * @return void
     **/
    public function getOptions()
    {
        if (!empty($this->data['options'])) {
            return unserialize($this->data['options']);
        } else {
            return "";
        }
    }
    // }}}

    // {{{ setDelivery()
    /**
     * @brief setDelivery
     *
     * @param mixed $param
     * @return void
     **/
    public function setDelivery($param)
    {
        if (!$this->initialized) {
            $this->data['delivery'] = $param;
        } else {
            $this->data['delivery'] = "," . implode(",", $param) . ",";
            $this->dirty['delivery'] = true;
        }
    }
    // }}}
    // {{{ getDelivery()
    /**
     * @brief getDelivery
     *
     * @param mixed
     * @return void
     **/
    public function getDelivery()
    {
        if (!empty($this->data['delivery'])) {
            return explode(",", trim($this->data['delivery'], ","));
        } else {
            return [];
        }
    }
    // }}}
    // {{{ addDelivery()
    /**
     * @brief adds a delivery method through which the notification should be delivered
     *
     * @param mixed $
     * @return void
     **/
    public function addDelivery($method)
    {
        $delivery = $this->delivery;

        $delivery[] = $method;
        array_unique($delivery);

        $this->delivery = $delivery;

        return $this;
    }
    // }}}
    // {{{ delivered()
    /**
     * @brief marks a delivery method as delivered and deletes notifications if no delivery methods are left
     *
     * @param mixed $
     * @return void
     **/
    public function delivered($method)
    {
        $delivery = $this->delivery;

        $index = array_search($method, $delivery);
        if ($index !== false) {
            array_splice($delivery, $index, 1);

            $this->delivery = $delivery;
        }

        if (count($delivery) == 0) {
            $this->delete();

            return null;
        }
        if (count($delivery) > 0) {
            $this->save();

            return $this;
        }
    }
    // }}}

    // {{{ save()
    /**
     * save a notification object
     *
     * @public
     */
    public function save() {
        $fields = array();
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        if ($isNew) {
            $this->date = date("Y-m-d H:i:s");
        }

        $dirty = array_keys($this->dirty, true);

        if (count($dirty) > 0) {
            if ($isNew) {
                $query = "INSERT INTO {$this->pdo->prefix}_notifications";
            } else {
                $query = "UPDATE {$this->pdo->prefix}_notifications";
            }
            foreach ($dirty as $key) {
                $fields[] = "$key=:$key";
            }
            $query .= " SET " . implode(",", $fields);

            if (!$isNew) {
                $query .= " WHERE $primary=:$primary";
                $dirty[] = $primary;
            }

            $params = array_intersect_key($this->data,  array_flip($dirty));

            $cmd = $this->pdo->prepare($query);
            $success = $cmd->execute($params);

            if ($isNew) {
                $this->data[$primary] = $this->pdo->lastInsertId();
            }

            if ($success) {
                $this->dirty = array_fill_keys(array_keys(static::$fields), false);
            }

            if (in_array("mail", $this->delivery)) {
                $delivery = new \Depage\Notifications\MailDelivery($this->pdo, "notifications@scriptdock.de");
                $delivery->addDeliveryTask();
            }
        }
    }
    // }}}
    // {{{ delete()
    /**
     * @brief deletes a notifification object
     *
     * @param mixed
     * @return void
     **/
    public function delete()
    {
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        if (!$isNew) {
            $query = $this->pdo->prepare("DELETE FROM {$this->pdo->prefix}_notifications WHERE $primary=:primary");
            $sucess = $query->execute(array(
                'primary' => $this->data[$primary],
            ));
        }

        return true;
    }
    // }}}

    // {{{ updateSchema()
    /**
     * @brief updateSchema
     *
     * @return void
     **/
    public static function updateSchema($pdo)
    {
        $schema = new \Depage\Db\Schema($pdo);

        $schema->setReplace(
            function ($tableName) use ($pdo) {
                return $pdo->prefix . $tableName;
            }
        );
        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
        $schema->update();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
