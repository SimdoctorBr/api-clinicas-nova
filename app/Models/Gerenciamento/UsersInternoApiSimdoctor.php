<?php

namespace App\Models\Gerenciamento;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;

class UsersInternoApiSimdoctor extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject {

    use Authenticatable,
        Authorizable,
        HasFactory;

    protected $connection = 'gerenciamento';
    protected $table = 'api_simdoctor_users';

  

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'login'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

    public function validateLogin($idsDominios, $email, $senha) {
        return $this->where('email', $email)
                        ->where('senha', md5($senha))
                        ->whereIn('identificador', $idsDominios)
                        ->first();
    }

    public function validateLoginTokenBio($idsDominios, $tokenBio) {
        return $this->where('auth_token_docbiz', $tokenBio)
                        ->whereIn('identificador', $idsDominios)
                        ->first();
    }
  

}
