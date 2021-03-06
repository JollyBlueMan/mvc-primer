<?php
namespace Controllers;

use Framework\Model;
use Framework\Session\Driver\Server;
use Models\File;
use Models\Friend;
use Models\User;
use Shared\Controller;
use Framework\Registry;
use Framework\RequestMethods;
use stdClass;

class Users extends Controller
{
    public function register()
    {
        $view  = $this->getActionView();

        if (RequestMethods::post("register") && empty(RequestMethods::post("honeypot"))) {
            $user = new User([
                "first"    => RequestMethods::post("first"),
                "last"     => RequestMethods::post("last"),
                "email"    => RequestMethods::post("email"),
                "password" => RequestMethods::post("password")
            ]);

            if ($user->validate()) {
                $user->save();
                $this->_upload("photo", $user->id);
                $view->set("success", true);
            } else {
                $view->set("errors", $user->getErrors());
                $view->set("success", false);
            }
        } else {
            $view->set("success", false);
        }

        $view->render();
    }

    public function login()
    {
        $view  = $this->getActionView();

        if (RequestMethods::post("login")) {
            $email    = RequestMethods::post("email");
            $password = RequestMethods::post("password");

            $error = false;

            if (empty($email)) {
                $view->set("email_error", "Email not provided");
                $error = true;
            }

            if (empty($password)) {
                $view->set("password_error", "Password not provided");
                $error = true;
            }

            if (!$error) {
                $user = User::first([
                    "email = ?"    => $email,
                    "password = ?" => $password,
                    "live = ?"     => true,
                    "deleted = ?"  => false
                ]);

                if (!empty($user)) {
                    /** @var Server $session */
                    $session = Registry::get("session");
                    $session->set("user", serialize($user));
                    $this->user = $user;

                    self::redirect("/public/profile");
                } else {
                    $view->set("password_error", "Email address and/or password are incorrect");
                }
            }
        }

        $view->render();
    }

    public function profile()
    {
        $view = $this->getActionView();
        $user = $this->user;

        if (empty($user)) {
            $user = new StdClass();
            $user->first = "Mx. ";
            $user->last  = "Smyf";
        }

        $view->set("user", $user);

         $view->render();
    }

    public function search()
    {
        $view = $this->getActionView();

        $query      = trim(RequestMethods::post("query"));
        $query      = "%{$query}%";
        $order      = RequestMethods::post("order", "modified");
        $direction  = RequestMethods::post("direction", "desc");
        (int)$page  = RequestMethods::post("page", 1);
        (int)$limit = RequestMethods::post("limit", 10);

        $count = 0;
        $users = false;

        if (RequestMethods::post("search")) {
            $where = [
                "CONCAT(first, ' ', last) LIKE ?" => $query,
                "live = ?"    => true,
                "deleted = ?" => false
            ];

            $fields = [
                "id", "first", "last"
            ];

            $count = User::count($where);
            $users = User::all($where, $fields, $order, $direction, $limit, $page);

            $view
                ->set("query", trim(str_replace("%", "", $query)))
                ->set("order", $order)
                ->set("direction", $direction)
                ->set("page", $page)
                ->set("limit", $limit)
                ->set("count", $count)
                ->set("users", $users);
        }

        $view->render();
    }

    public function settings()
    {
        $view = $this->getActionView();
        $userCurrent = $this->user;
        $view->set("user", $userCurrent);

        if (RequestMethods::post("update")) {
            $user = new User([
                "id"       => $userCurrent->id,
                "first"    => RequestMethods::post("first", $userCurrent->first),
                "last"     => RequestMethods::post("last", $userCurrent->last),
                "email"    => RequestMethods::post("email", $userCurrent->email),
                "password" => RequestMethods::post("password", $userCurrent->password),
                "live"     => $userCurrent->live,
                "deleted"  => $userCurrent->deleted,
                "created"  => $userCurrent->created
            ]);

            if ($user->validate()) {
                $user->save();
                $this->_upload("photo", $userCurrent->id);

                $newUser = User::first(["id = ?" => $userCurrent->id]);
                /** @var Server $session */
                $session = Registry::get("session");
                $session->set("user", serialize($newUser));

                $view->set("success", true);
            } else {
                $view->set("errors", $user->getErrors());
                $view->set("success", false);
            }
        }
        else
        {
            $view->set("success", false);
        }

         $view->render();
    }

    public function logout()
    {
        $this->setUser(false);

        /** @var Server $session */
        $session = Registry::get("session");
        $session->erase("user");

        self::redirect("/public/login");
    }

    /**
     * @before _secure
     */
    public function friend($id)
    {
        $user = $this->getUser();

        $friend = new Friend([
            "user"   => $user->id,
            "friend" => $id
        ]);

        $friend->save();

        self::redirect("/public/search");
    }

    /**
     * @before _secure
     */
    public function unfriend($id)
    {
        $user = $this->getUser();

        $friend = Friend::first([
            "user = ?"   => $user->id,
            "friend = ?" => $id
        ]);

        if ($friend) {
            $friend = new Friend([
                "id" => $friend->id
            ]);
            $friend->delete();
        }

        self::redirect("/public/search");
    }

    protected function _upload($name, $user)
    {
        if (isset($_FILES[$name]))
        {
            $file      = $_FILES[$name];
            $path      = APP_PATH . "/public/uploads/";
            $time      = (new \DateTime())->format("His");
            $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
            $filename  = "{$user}--{$time}.{$extension}";

            if (move_uploaded_file($file["tmp_name"], $path . $filename)) {
                $meta = getimagesize($path . $filename);

                if ($meta) {
                    $width  = $meta[0];
                    $height = $meta[1];

                    $file = new File([
                        "name"   => $filename,
                        "mime"   => $file["type"],
                        "size"   => $file["size"],
                        "width"  => $width,
                        "height" => $height,
                        "user"   => $user
                    ]);

                    $file->save();
                }
            }
        }
    }

    /**
     * @param $id
     * @throws Model\Exception\Validation
     * @before _secure, _admin
     */
    public function edit($id)
    {
        $errors = [];

        $editUser = User::first([
            "id = ?" => $id
        ]);

        if (RequestMethods::post("save"))
        {
            $editUser->first    = RequestMethods::post("first");
            $editUser->last     = RequestMethods::post("last");
            $editUser->email    = RequestMethods::post("email");
            $editUser->password = RequestMethods::post("password");
            $editUser->live     = (boolean) RequestMethods::post("live");
            $editUser->admin    = (boolean) RequestMethods::post("admin");

            if ($editUser->validate()) {
                $editUser->save();
                $this->actionView->set("success", true);
            }

            $errors = $editUser->errors;
        }

        $this->actionView->set("editUser", $editUser)->set("errors", $errors);
    }

    /**
     * @before _secure, _admin
     */
    public function view()
    {
        $this->actionView->set("users", User::all());
    }

    /**
     * @param $id
     * @before _secure, _admin
     */
    public function delete($id)
    {
        $user = User::first([
            "id = ?" => $id
        ]);

        if ($user) {
            $user->live = false;
            $user->save();
        }

        self::redirect("/public/users/view");
    }

    /**
     * @param $id
     * @before _secure, _admin
     */
    public function undelete($id)
    {
        $user = User::first([
            "id = ?" => $id
        ]);

        if ($user) {
            $user->live = true;
            $user->save();
        }

        self::redirect("/public/users/view");
    }
}
