<?php
namespace Models;

use Shared\Model;

class User extends Model
{
    /**
     * @var
     * @readwrite
     */
    protected $_table = "user";

    /**
     * @var $_first
     * @column
     * @readwrite
     * @type text
     * @length 100
     *
     * @validate required, alpha, min(3), max(32)
     * @label first name
     */
    protected $_first;

    /**
     * @var $_last
     * @column
     * @readwrite
     * @type text
     * @length 100
     *
     * @validate required, alpha, min(3), max(32)
     * @label last name
     */
    protected $_last;

    /**
     * @var $_email
     * @column
     * @readwrite
     * @type text
     * @length 100
     * @index
     *
     * @validate required, max(100)
     * @label email address
     */
    protected $_email;

    /**
     * @var $_password
     * @column
     * @readwrite
     * @type text
     * @length 500
     * @index
     *
     * @validate required, min(8), max(32)
     * @label password
     */
    protected $_password;

    /**
     * @var $_admin
     * @column
     * @readwrite
     * @type boolean
     */
    protected $_admin = false;

    public function isFriend($id)
    {
        $friend = Friend::first([
            "user = ?"   => $this->getId(),
            "friend = ?" => $id
        ]);

        if ($friend)
        {
            return true;
        }

        return false;
    }

    public static function hasFriend($id, $friend)
    {
        $user = new self([
            "id" => $id
        ]);

        return $user->isFriend($friend);
    }

    public static function getNameById($id)
    {
        $user = self::first([
            "id = ?"      => $id,
            "live = ?"    => true,
            "deleted = ?" => false
        ]);

        if ($user)
        {
            return  $user->first . " " . $user->last;
        }
        return "";
    }

    public function getFile()
    {
        return File::first([
            "user = ?"    => $this->id,
            "live = ?"    => true,
            "deleted = ?" => false
        ], ["*"], "id", "desc");
    }
}
