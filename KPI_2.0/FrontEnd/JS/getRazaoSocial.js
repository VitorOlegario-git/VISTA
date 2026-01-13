function getRazaoSocial(cnpj) {
    console.log("getRazaoSocial called with CNPJ:", cnpj);

    $.ajax({
        url: 'buscar_cliente.php',
        method: 'POST',
        data: { cnpj: cnpj },
        success: function(data) {
            // Define o valor da razão social no campo correspondente
            $('#razao_social').val(data);
        },
        error: function(xhr, status, error) {
            console.error('Erro ao obter a razão social:', error);
        }
    });
}


