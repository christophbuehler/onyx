<?php

namespace Onyx\Libs;

class User
{
  public $id;
  private $roles = [];
  private $isAuthenticated = false;

  function __construct()
  {

  }

  public function authenticate(int $id)
  {
    $this->id = $id;
    $this->isAuthenticated = true;
  }

  public function set_roles(array $roles)
  {
    $this->roles = $roles;
  }
  
  public function has_role(string $role)
  {
    foreach ($this->roles as $r)
      if ($r == $role) return true;
    return false;
  }

  public function is_authenticated()
  {
    return $this->isAuthenticated;
  }
}
