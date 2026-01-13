function applyCNPJMask(element) {
    let value = element.value;
    value = value.replace(/\D/g, "");
    value = value.replace(/^(\d{2})(\d)/, "$1.$2");
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
    value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
    value = value.replace(/(\d{4})(\d)/, "$1-$2");
    element.value = value;
}

// Função para inicializar a máscara de CNPJ
function initializeCNPJMask() {
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            applyCNPJMask(this);
        });
    }
}

function checkAndHandleEnter(event) {
if (event.key === 'Enter') {
event.preventDefault();
handleEnter();
}
} 