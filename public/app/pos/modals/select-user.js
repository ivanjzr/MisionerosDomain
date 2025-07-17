define(function(){
    function moduleReady(modal, s_data){
        console.log(s_data);
    

        
        let record_id = (s_data.pos_id) ? s_data.pos_id : s_data.pos_register_id;
        let pos_register_title = (s_data.pos_register_title) ? s_data.pos_register_title : record_id;
        
        //
        $("#pos-name").html("Seleccionar usuario para POS " + pos_register_title);


      

        //
        app.loadModalSetPinNumber = function(user_id, user_name){
            //
            $("#modal-select-user").find('.modal').modal("hide");

            //
            let modal_data = {
                user_id,
                user_name,
                pos_register_title
            }

            //
            if (s_data.pos_id){
                modal_data.pos_id = s_data.pos_id;
            } else if (s_data.pos_register_id){
                modal_data.pos_register_id = s_data.pos_register_id;
            }
            

            //
            loadModalV2({
                id: "modal-set-pin",
                modal_size: "sm",
                data: modal_data,
                /*onHide: function(){},*/
                html_tmpl_url: "/app/pos/modals/set-user-pin.html?v=" + dynurl(),
                js_handler_url: "/app/pos/modals/set-user-pin.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    //disable_btns();
                },
                onInit: function(){
                    //
                    enable_btns();
                }
            });
        }
        

        //
       app.createUserItem = function(item){
            //console.log(item);
            // 
            return `
                <div class="col-12">
                    <div class="user-card h-100">
                        <button class="btn btn-outline-info w-100 h-100 p-3" onclick="app.loadModalSetPinNumber(${item.id}, '${item.name}')">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                </div>
                                <div class="user-info text-start flex-grow-1">
                                    <div class="user-name h6 mb-0 text-dark">${item.id} - ${item.name}</div>
                                </div>
                                <div class="user-action">
                                    <i class="fas fa-arrow-right text-primary"></i>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            `;
        }



        app.onPosUsersReady = function(posUsers) {
            //
            const container = $('#users_list');
            container.empty();

            if (!posUsers || posUsers.length === 0) {
                container.html(`
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <h5>No hay usuarios disponibles</h5>
                            <p>Contacta al administrador para configurar los usuarios.</p>
                        </div>
                    </div>
                `);
                return;
            }

            posUsers.forEach(pos => {
                const item = app.createUserItem(pos);
                container.append(item);
            });

            //
            container.find('button').first().focus();
        };



        


        app.loadPosUsers = function(){
            //
            $.ajax({
                type: "GET",
                url: app.admin_url + "/pos/users",
                success: function(data) {
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    
                    if (data && data.length){
                        app.onPosUsersReady(data);
                    } else {
                        app.onPosUsersReady([]);
                    }
                },
                error: function(xhr, status, error) {
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //console.error("Error: " + error);
                    
                    // Mostrar error
                    $('#users_list').html(`
                        <div class="col-12">
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <h5>Error al cargar los usuarios</h5>
                                <p>No se pudieron cargar los usuarios. Por favor, intente nuevamente.</p>
                                <button class="btn btn-outline-danger" onclick="app.loadPosUsers()">
                                    <i class="fas fa-retry me-2"></i>
                                    Reintentar
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        }



        //
        app.loadPosUsers();

    }
    return {init: moduleReady}
});