<?php

namespace App\Repositories\Clinicas;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

class EmpresaRepository extends BaseRepository {
    
    
    public function getById($dominioId) { 
        
        $qr = $this->connClinicas()->select("SELECT * FROM minisite WHERE identificador= '$dominioId'");
  
        return $qr;
    }
    public function getListByIds(Array $dominiosId) { 
        
        $qr = $this->connClinicas()->select("SELECT * FROM minisite WHERE identificador IN( ".implode(',',$dominiosId).")");
  
        return $qr;
    }
    
    
    
    
    
    
}
