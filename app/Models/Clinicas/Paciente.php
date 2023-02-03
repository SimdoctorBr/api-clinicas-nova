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

class Paciente extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject {

    use Authenticatable,
        Authorizable,
        HasFactory;

    protected $connection = 'clinicas';
    protected $table = 'pacientes';

    public function getAuthPassword() {
        return $this->senha;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nome', 'email'
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

    public function validateLogin($idDominio, $email, $senha) {
        return $this->where('identificador', $idDominio)
                        ->whereRaw("AES_DECRYPT(email_cript,'" . env('APP_ENC_CODE') . "') = '$email'")
                        ->where('senha', $senha)->first();
    }

    public function validateLoginTokenBio($idDominio, $tokenBio) {


        return $this->where('auth_token_biometria', $tokenBio)
                        ->where('identificador', $idDominio)
                        ->first();
    }

    public function isExistsLogin($idDominio, $email) {



        $qr = DB::connection('clinicas')->select("SELECT id FROM pacientes WHERE identificador = $idDominio AND CAST(AES_DECRYPT( email_cript, '" . env('APP_ENC_CODE') . "' ) AS CHAR(255)) =  '$email' ");

        if (count($qr) > 0) {
            return $qr;
        } else {
            return false;
        }
    }

    public function storeLogin($dadosLogin) {



        $dataCadastro = time();

        $qr = DB::connection('clinicas')->table('pacientes')->insert([
            'nome_cript' => DB::RAW("AES_ENCRYPT( '{$dadosLogin['nome']}','" . env('APP_ENC_CODE') . "')"),
            'sobrenome_cript' => DB::RAW("AES_ENCRYPT( '{$dadosLogin['sobrenome']}','" . env('APP_ENC_CODE') . "')"),
            'email_cript' => DB::RAW("AES_ENCRYPT( '{$dadosLogin['email']}','" . env('APP_ENC_CODE') . "')"),
            'senha' => $dadosLogin['senha'],
            'identificador' => $dadosLogin['identificador'],
            'data_cadastro' => $dataCadastro
        ]);
            
        $idInsert =  DB::connection('clinicas')->getPdo()->lastInsertId();
            
        return $idInsert;
    }

}
