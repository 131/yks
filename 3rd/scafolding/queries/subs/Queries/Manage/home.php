<?

if($action == "query_add") try {


    $data = array(
        'query_name'=>$_POST['query_name'],
        'query_def'=>specialchars_decode($_POST['query_def']),
        'query_descr'=>$_POST['query_descr'],
    );
    
    $query  = queries_manager::create($data);

    // rbx::ok("Votre requete a bien été créée : $query");

    reloc("/?&queries_fold;//{$query->query_id}/Manage");
} catch(rbx $e){}


if($action == "query_manage") try {

    $data = array(
        'query_name'=>$_POST['query_name'],
        'query_def'=>specialchars_decode($_POST['query_def']),
        'query_descr'=>$_POST['query_descr'],
    );
    $query->update($data);

    rbx::ok("Votre requete a bien été modifiée");

} catch(rbx $e){}




tpls::export(array(
    'query_action' => $query ? 'query_manage' : 'query_add'
));