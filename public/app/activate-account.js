$(document).ready(function(){




    //
    $('#loading').show();
    $('#status_text').text("Estamos activando tu cuenta por favor espera...");


    //
    if ( (activation_code = getParameterByName("ac")) ){
        //
        $.ajax({
            type:'POST',
            url: api_url + "/auth/activate-business",
            dataType: "json",
            data: JSON.stringify({
                activation_code: activation_code
            }),
            beforeSend: function( xhr ) {
                //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                disable_btns();
                preload(true);
            },
            contentType: "application/json",
            success:function(response){
                //console.log(response); return;
                //
                enable_btns();
                preload(false);

                //
                $('#loading').hide();
                $('#status_text').text("Estamos activando tu cuenta por favor espera...");

                //
                if (response.success){
                    //
                    MessageInterval('#status_text', 'success', 3, 0, "Cuenta activada correctamente", function(){
                        location.href = "/app";
                    });
                }

                //
                else if (response.error){
                    //
                    MessageInterval('#status_text', 'danger', 3, 0, response.error, function(){
                        location.href = "/app";
                    });
                }

                //
                else {
                    //
                    MessageInterval('#status_text', 'danger', 3, 0, "Error al intentar activar la cuenta", function(){
                        location.href = "/app";
                    });
                }
            },
            error: function(){
                //
                enable_btns();
                preload(false);
                //
                $('#preloader').hide();

                //
                MessageInterval('#status_text', 'danger', 3, 0, "Error al intentar activar la cuenta", function(){
                    location.href = "/app";
                });

            }
        });
    }
    //
    else {
        location.href = "/app";
    }






});
