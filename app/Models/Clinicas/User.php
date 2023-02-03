<?php

namespace App\Models\Clinicas;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject {

    use Authenticatable,
        Authorizable,
        HasFactory;

    protected $connection = 'clinicas';
    protected $table = 'administradores';

    public function getAuthPassword() {
        return $this->senha;
    }

    public function doutores() {

        $this->belongsTo('doutores', 'doutor_user_vinculado');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nome', 'email', 'login', 'doutores'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'senha',
    ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

   public function validateLogin($idsDominios, $email, $senha, $idUserSelect = null) {



        $qr = $this->where('email', $email)
                ->where('senha', md5($senha))
                ->where('status_usuario', 1)
                ->whereIn('identificador', $idsDominios);
        if (!empty($idUserSelect)) {
            $qr = $qr->where('id', $idUserSelect);
        }
        return $qr->get();
    }

    public function validateLoginTokenBio($idsDominios, $tokenBio) {
        return $this->where('auth_token_docbiz', $tokenBio)
                        ->where('status_usuario', 1)
                        ->whereIn('identificador', $idsDominios)->get();
    }

}
