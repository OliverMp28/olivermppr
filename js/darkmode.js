    /*---------------MODO OSCURO -------------------- */
    const botonDark = document.getElementById("boton-darkmode");
    const body = document.body;

    // Solo añadir el listener si el botón existe en la página
    if (botonDark) {
        botonDark.addEventListener("click", function() {
            let val = body.classList.toggle("dark");
            localStorage.setItem("modo", val);
        });
    }

    // Aplicar el modo oscuro si está guardado en localStorage
    const valor = localStorage.getItem("modo");
    if (valor === "true") {
        body.classList.add("dark");
        // Sincronizar el checkbox si existe
        if(botonDark) {
            document.getElementById('check-darkmode').checked = true;
        }
    } else {
        body.classList.remove("dark");
    }