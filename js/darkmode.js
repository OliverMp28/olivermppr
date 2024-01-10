    /*---------------MODO OSCURO -------------------- */
    let botonDark=document.getElementById("boton-darkmode");
    let body=document.body;

    botonDark.addEventListener("click", function(){
        let val=body.classList.toggle("dark");
        localStorage.setItem("modo",val)
    })

    let valor=localStorage.getItem("modo")

    if (valor=="true") {
        body.classList.add("dark")
    } else {
        body.classList.remove("dark")
    }