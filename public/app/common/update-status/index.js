define(function(){
    function moduleReady(modal, section_data){
        console.log(modal, section_data);



        //
        $('#modal-title').text("Update Status Sale #" + section_data.id);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Update");


        //
        var filter_status_id = $("#filter_status_id").val();
        //
        loadSelectAjax({
            id: "#status_id",
            url: app.admin_url + "/sale-status/list?ut="+app.update_type,
            parseFields: function(item){
                return item.status_title;
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--select new status",
            enable: true,
            focus: true,
            default_value: (filter_status_id) ? filter_status_id : null,
            onChange: function(){
                //
            }
        });



        //
        dataGrid({
            gridId: "#grid_sale_status",
            url: app.admin_url + "/sales/" + section_data.id + "/status/?ut="+app.update_type,
            columns: [
                {"name" : "id", "data" : "id"},
                {"name": "status_title", "data" : "status_title"},
                {"name": "status_notes", "data" : "status_notes"},
                {"name": "username", "data" : "username"},
                {"data" : function(obj){ return fmtDateEng(obj.datetime_created.date);}},
            ],
            columnDefs: [
                {
                    "targets": "_all",
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            order: [[ 0, "desc" ]],
            gridReady: function(){
                //
            }
        });



        //
        $("#form_section").validate();
        //
        $("#form_section").submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $("#form_section").valid() ) {
                //
                $("#form_section").ajaxSubmit({
                    url: app.admin_url + "/sales/" + section_data.id + "/status/update",
                    beforeSubmit: function(arr){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay", true);
                    },
                    success: function(send_response){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        if (send_response && send_response.id){

                            //
                            app.Toast.fire({ icon: 'success', title: "Status updated" });
                            //$("#modal-update-status").find('.modal').modal("hide");
                            //
                            $("#grid_sale_status").DataTable().ajax.reload();
                            $("#grid_section").DataTable().ajax.reload();

                        }
                        //
                        else if (send_response.error){
                            app.Toast.fire({ icon: 'error', title: send_response.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response_error){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });
            }
        });




    }
    return {init: moduleReady}
});






