define(function(){
    function moduleReady(modal, section_data){
        //console.log(modal, section_data);

        //
        $('#modal-title').text("Sale #" + section_data.id + " Notifications");
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Add");

        //
        var template_status_update_id = 9;

        //
        loadSelectAjax({
            id: "#msg_id",
            url: app.admin_url + "/templates/messages/type/" + template_status_update_id,
            parseFields: function(item){
                return item.maqueta_nombre + " (" + item.id + ")";
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--all",
            enable: true,
            focus: true,
            onChange: function(){
                setMsgId();
            },
            onReady: function(){
                setMsgId();
            }
        });


        //
        function setMsgId(){
            var msg_info = $("#msg_id").find(':selected').data('info');
            if (msg_info && msg_info.id){
                $("#msg").val(msg_info.sms_msg);
            } else {
                $("#msg").val("");
            }
        }


        //
        dataGrid({
            gridId: "#grid_sale_msgs",
            url: app.admin_url + "/sales/" + section_data.id + "/msgs",
            columns: [
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                    return obj.msg_template_name + " (" + obj.msg_id + ")";
                }},
                {"name": "msg", "data" : "msg"},
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
                    url: app.admin_url + "/sales/" + section_data.id + "/msgs",
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
                            app.Toast.fire({ icon: 'success', title: "Msg Sent" });
                            //$("#modal-update-status").find('.modal').modal("hide");
                            //
                            $("#grid_sale_msgs").DataTable().ajax.reload();
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






