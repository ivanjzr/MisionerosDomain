define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_post_visits",
            section_title: "Posts",
            data: section_data,
            section_title_singular: "Post",
            scripts_path: "/app/blog",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/visits",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"visible": false, "name" : "id", "data" : "id"},
                    {"visible": false, "name" : "post_id", "data" : "post_id"},
                    {"name": "identifier", "data" : "identifier"},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                            //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){
                //
            },
            onSectionReady: function(opts){
                //
                $('#btnReloadVisits').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_post_visits").DataTable().ajax.reload();
                });
            }
        });



    }
    return {init: moduleReady}
});