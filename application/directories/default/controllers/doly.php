<?php

/**
* Your copy paste Controller.
*/
Class Doly extends Controller {
    
    function __construct()
    {   
        parent::__construct();
        parent::__global();
    
    }                               

    public function index()
    {
        echo 'ok';
        $this->title = '';

        $this->body  = view('');
        view_app('');
    }
    
}

/* End of file start.php */
/* Location: .application/default/controllers/start.php */