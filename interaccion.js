var time = new Date();
var deltaTime = 0;

//aca da la señal al cargar la pagina
/*if (document.readyState === "complete" || document.readyState === "interactive"){
    setTimeout(AudioLoad,1);
    
}else{
    document.addEventListener("DOMContentLoaded", AudioLoad);
}*/


var sueloY = 22;
var velY = 0;
var impulso = 900;
var gravedad = 2500;

var dinoPosX = 42;
var dinoPosY = sueloY; 

var sueloX = 0;
var velEscenario = 1280/3;
var gameVel;

var score = 0;

var parado = false;
var saltando = false;

var tiempoHastaObstaculo = 2;
var tiempoObstaculoMin = 0.7;
var tiempoObstaculoMax = 1.8;
var obstaculoPosY = 16;
var obstaculos = [];

var tiempoHastaNube = 0.5;
var tiempoNubeMin = 0.7;
var tiempoNubeMax = 2.7;
var maxNubeY = 270;
var minNubeY = 100;
var nubes = [];
var velNube = 0.5;

var contenedor;
var dino;
var textoScore;
var suelo;
var gameOver;
var tryAgain;
var detectarFin = false;
var x = false;
var obstaculo;

var botonPlay = document.getElementById("play");
var audio1;
audio1 = document.getElementById("audio");
var cancion = document.getElementById('cancion');
var audioFinal = document.getElementById("final");
var audioPi = document.getElementById("pipipi");

var areaTouch= document.getElementById("cuerpo");

var nuevaCancion;
var caja1 = document.querySelector("#juegazo");
var cajaLista = document.querySelector(".lista");
var play = document.querySelector(".contenedor-boton");

//botones
const boton1= document.getElementById("boton1");
const boton2= document.getElementById("boton2");
const boton3= document.getElementById("boton3");
const boton4= document.getElementById("boton4");
const boton5= document.getElementById("boton5");
const boton6= document.getElementById("boton6");
const boton7= document.getElementById("boton7");

botonPlay.addEventListener("click", Iniciar);
function Iniciar(){
    AudioOn();
    play.style.display ="none";
}

//ejecutar las funciones para el inicio de cada nivel
boton1.addEventListener("click", accion1);
boton2.addEventListener("click", accion2);
boton3.addEventListener("click", accion3);
boton4.addEventListener("click", accion4);
boton5.addEventListener("click", accion5);
boton6.addEventListener("click", accion6);
boton7.addEventListener("click", accion7);

//asignar constantes para los nombres de cada cancion
const nombreCancion1 = document.getElementById("nombre-cancion1");
const nombreCancion2= document.getElementById("nombre-cancion2");
const nombreCancion3= document.getElementById("nombre-cancion3");
const nombreCancion4= document.getElementById("nombre-cancion4");
const nombreCancion5= document.getElementById("nombre-cancion5");
const nombreCancion6= document.getElementById("nombre-cancion6");
const nombreCancion7= document.getElementById("nombre-cancion7");
const nombre=document.getElementById("nombreJuego");

function accion1(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/Sugar Red.mp3";
    /*nuevaCancion = cancion0.cloneNode(true);
    audio1.appendChild(nuevaCancion);*/
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion1.innerHTML;
}

function accion2(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/HOPE.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion2.innerHTML;
}

function accion3(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/Running With the wolves - Aurora.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion3.innerHTML;
}

function accion4(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/Time.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion4.innerHTML;
}

function accion5(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/Mi Fiesta.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion5.innerHTML;
}

function accion6(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/Dawn of Faith - Eternal Eclipse.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion6.innerHTML;
}

function accion7(){
    caja1.style.display ="block";
    cajaLista.style.display = "none";
    var cancion0 = document.getElementById("cancion0");
    cancion0.src = "audios/What's Up Danger.mp3";
    play.style.display = "block";

    AudioLoad();

    //obtener nombre de cancion
    nombre.innerHTML=nombreCancion7.innerHTML;
}


var repetir = document.getElementById("repetir");

repetir.addEventListener("click", Reiniciar);

function Reiniciar(){
    for(let i = 0; i<=obstaculos.length; i++){
        obstaculos[i].style.display="none";
        gameOver.style.display = "none";
        tryAgain.style.display = "none";
        x=false;
        audio1.currentTime=0;
        audio1.play();
        JuegoPlay();
        score = 0; 
    }
}

//esto escucha cuando el audio esta reproduciendo ejecuta la funcion Loop, que ejecutara el juego
if(audio1!=0){
    audio1.addEventListener("play", function(){
        Init();
        if(parado==false){
            document.addEventListener("click", Saltar);
        }
    });
}


function detectar(){
    if(x==false){
        window.addEventListener("blur", function() {
            audio1.pause();
            JuegoStop();
        });
    
        window.addEventListener("focus", function() {
          if(!x){
            audio1.play();
            JuegoPlay();
          }

        });
          
     }
    else if(x==true){
        parado=true;
        audio1.pause();
        audio1 = 0;
        return audio1;
    }
}

function Init(){
    time = new Date();
    Start();
    Loop();
    myFunctionAudio();
    detectar();


    dino.classList.add("dino-corriendo");   
}

function Loop(){
    deltaTime = (new Date() - time) / 1000;
    time = new Date();
    Update();
    requestAnimationFrame(Loop);
}

function Start() {
    gameOver = document.querySelector(".game-over");
    suelo = document.querySelector(".suelo");
    contenedor = document.querySelector(".contenedor");
    textoScore = document.querySelector(".score");
    dino = document.querySelector(".dino");
    window.addEventListener("keydown", HandleKeyDown);
    tryAgain = document.querySelector(".jugar-denuevo");
    youWin = document.querySelector(".you-win");
    
}

function Update() {
    if(parado) return;
    
    MoverDinosaurio();
    MoverSuelo();
    DecidirCrearObstaculos();
    DecidirCrearNubes();
    MoverObstaculos();
    MoverNubes();
    DetectarColision();

    velY -= gravedad * deltaTime;
}

function HandleKeyDown(evento){       
    if(evento.keyCode == 32 || evento.keyCode == 38){
        Saltar();
        evento.preventDefault();
    }
}

areaTouch.addEventListener('touchstart', function detectarTouch(event){
    //Comprobamos si hay varios eventos del mismo tipo
    if (event.targetTouches.length == 1) { 
    var touch = event.targetTouches[0]; 
    // con esto solo se procesa UN evento touch
    Saltar();
    }
    
    }, false);

function Saltar(){
    if(dinoPosY === sueloY){
        saltando = true;
        velY = impulso;
        dino.classList.remove("dino-corriendo");    
    }
}

function MoverDinosaurio() {
    dinoPosY += velY * deltaTime;
    if(dinoPosY < sueloY){
        
        TocarSuelo();
    }
    dino.style.bottom = dinoPosY+"px";
}

function TocarSuelo() {
    dinoPosY = sueloY;
    velY = 0;
    if(saltando){
        dino.classList.add("dino-corriendo");
    }
    saltando = false;
}

function MoverSuelo() {
    sueloX += CalcularDesplazamiento();
    suelo.style.left = -(sueloX % contenedor.clientWidth) + "px";
}

//movil
if(screen.width < 767){
    gameVel = 0.7;

    function CalcularDesplazamiento() {
        return velEscenario * deltaTime * gameVel; 
    }

    function CrearObstaculo() {
         obstaculo = document.createElement("div");
        contenedor.appendChild(obstaculo);
        obstaculo.classList.add("cactus");
        if(Math.random() > 0.5) obstaculo.classList.add("cactus2");
        obstaculo.posX = contenedor.clientWidth;
        obstaculo.style.left = contenedor.clientWidth+"px";
    
        obstaculos.push(obstaculo);
        tiempoHastaObstaculo = tiempoObstaculoMin + Math.random() * (tiempoObstaculoMax-tiempoObstaculoMin) / gameVel;
        return obstaculos;
    }
    
    function CrearNube() {
        var nube = document.createElement("div");
        contenedor.appendChild(nube);
        nube.classList.add("nube");
        nube.posX = contenedor.clientWidth;
        nube.style.left = contenedor.clientWidth+"px";
        nube.style.bottom = minNubeY + Math.random() * (maxNubeY-minNubeY)+"px";
        
        nubes.push(nube);
        tiempoHastaNube = tiempoNubeMin + Math.random() * (tiempoNubeMax-tiempoNubeMin) / gameVel;
    }

    function GanarPuntos() {
        score++;
        textoScore.innerText = score;
        
        if(score < 5){
            gameVel = 0.7;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche"); 
        }
        else if(score >= 5 && score < 15){
            gameVel = 1.2;
            contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche");
            contenedor.classList.add("mediodia");
        }else if(score >= 15 && score < 25) {
            gameVel = 1.4;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("noche");
            contenedor.classList.add("tarde");
        } else if(score >= 25) {
            gameVel = 2.1;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.add("noche");
        }
        suelo.style.animationDuration = (3/gameVel)+"s";
    } 

    function DetectarColision() {
        for (var i = 0; i < obstaculos.length; i++) {
            if(obstaculos[i].posX > dinoPosX + dino.clientWidth) {
                //EVADE
                break; //al estar en orden, no puede chocar con más
            }else{
                if(IsCollision(dino, obstaculos[i], 10, 20, 15, 20)) {
                    GameOver();
                }
            }
        }
    }
}

//computadora
else if (screen.width > 767){
    gameVel = 1;

    function CalcularDesplazamiento() {
        return velEscenario * deltaTime * gameVel;
    }

    function CrearObstaculo() {
         obstaculo = document.createElement("div");
        contenedor.appendChild(obstaculo);
        obstaculo.classList.add("cactus");
        if(Math.random() > 0.5) obstaculo.classList.add("cactus2");
        obstaculo.posX = contenedor.clientWidth;
        obstaculo.style.left = contenedor.clientWidth+"px";
    
        obstaculos.push(obstaculo);
        tiempoHastaObstaculo = tiempoObstaculoMin + Math.random() * (tiempoObstaculoMax-tiempoObstaculoMin) / gameVel;
    }
    
    function CrearNube() {
        var nube = document.createElement("div");
        contenedor.appendChild(nube);
        nube.classList.add("nube");
        nube.posX = contenedor.clientWidth;
        nube.style.left = contenedor.clientWidth+"px";
        nube.style.bottom = minNubeY + Math.random() * (maxNubeY-minNubeY)+"px";
        
        nubes.push(nube);
        tiempoHastaNube = tiempoNubeMin + Math.random() * (tiempoNubeMax-tiempoNubeMin) / gameVel;
    }

    function GanarPuntos() {
        score++;
        textoScore.innerText = score;
        if(score < 5){
            gameVel = 1;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche"); 
        }
        else if(score >= 5 && score < 15){
            gameVel = 1.5;
            contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche");
            contenedor.classList.add("mediodia");
        }else if(score >= 15 && score < 25) {
            gameVel = 2;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("noche");
            contenedor.classList.add("tarde");
        } else if(score >= 25) {
            gameVel = 3;
            contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.add("noche");
        }
        suelo.style.animationDuration = (3/gameVel)+"s";
    } 

    function DetectarColision() {
        for (var i = 0; i < obstaculos.length; i++) {
            if(obstaculos[i].posX > dinoPosX + dino.clientWidth) {
                //EVADE
                break; //al estar en orden, no puede chocar con más
            }else{
                if(IsCollision(dino, obstaculos[i], 10, 30, 15, 20)) {
                    GameOver();
                }
            }
        }
    }
}


function Estrellarse() {
        dino.classList.remove("dino-corriendo");
        dino.classList.add("dino-estrellado");
        parado = true;
        AudioOff();
}

function DecidirCrearObstaculos() {
    tiempoHastaObstaculo -= deltaTime;
    if(tiempoHastaObstaculo <= 0) {
        CrearObstaculo();
    }
}

function DecidirCrearNubes() {
    tiempoHastaNube -= deltaTime;
    if(tiempoHastaNube <= 0) {
        CrearNube();
    }
}

function MoverObstaculos() {
    for (var i = obstaculos.length - 1; i >= 0; i--) {
        if(obstaculos[i].posX < -obstaculos[i].clientWidth) {
            obstaculos[i].parentNode.removeChild(obstaculos[i]);
            obstaculos.splice(i, 1);
            GanarPuntos();
        }else{
            obstaculos[i].posX -= CalcularDesplazamiento();
            obstaculos[i].style.left = obstaculos[i].posX+"px";
        }
    }
}

function MoverNubes() {
    for (var i = nubes.length - 1; i >= 0; i--) {
        if(nubes[i].posX < -nubes[i].clientWidth) {
            nubes[i].parentNode.removeChild(nubes[i]);
            nubes.splice(i, 1);
        }else{
            nubes[i].posX -= CalcularDesplazamiento() * velNube;
            nubes[i].style.left = nubes[i].posX+"px";
        }
    }
}



function GameOver() {
    Estrellarse();
    gameOver.style.display = "block";
    tryAgain.style.display = "block";
    Terminar();
    AudioPipipi();
}

function JuegoStop() {
    parado = true;
}

function JuegoPlay() {
    parado = false;
}

function IsCollision(a, b, paddingTop, paddingRight, paddingBottom, paddingLeft) {
    var aRect = a.getBoundingClientRect();
    var bRect = b.getBoundingClientRect();

    return !(
        ((aRect.top + aRect.height - paddingBottom) < (bRect.top)) ||
        (aRect.top + paddingTop > (bRect.top + bRect.height)) ||
        ((aRect.left + aRect.width - paddingRight) < bRect.left) ||
        (aRect.left + paddingLeft > (bRect.left + bRect.width))
    );
}


function AudioLoad(){
    audio1.load();
}
  
function AudioOn(){
       audio1.play();
}

function AudioPause(){
    audio1.pause();
};

function AudioOff(){
        audio1.pause();                  
}

function AudioFinalOn(){
        audioFinal.load();
        audioFinal.play();
}

function AudioPipipi(){
    audioPi.load();
    audioPi.play();
}

function Terminar(){
    x=true;
    return x;
}

audio1.addEventListener("ended", function(){
    Win();
    AudioFinalOn();
});



function Win(){
    AudioFinalOn();
    youWin.style.display = "block";
    tryAgain.style.display = "block";

    dino.classList.remove("dino-corriendo");
    dino.classList.add("dino-estrellado"); 
    parado = true;


    Terminar();
}

/* audio.addEventListener("timeupdate", "currentTime", "duration", function(){
    var duration = audio.duration;
    var currentTime = audio.currentTime;

    var prueba = document.getElementById("prueba");
    prueba.innerHTML = currentTime.toString();
});  */

    function myFunctionAudio(){      
    
    // ejecutamos con setInterval cada 1 seg la función verifica_fin()
    var fin =window.setInterval(function(){
        verifica_fin();
        },1000);
    }



    function verifica_fin(){
        var tiempoActual = audio1.currentTime; // recuperamos el tiempo actual de reproducción y lo redondeamos a un entero
        var segs = tiempoActual.toString(); // convertimos el tiempo actual a una cadena para poder formatearlo en hh:mm:ss
        // mandamos el tiempo actual a un div en pantalla
        // document.getElementById('comprobacion1').innerHTML = segs;

        var duracion = audio1.duration;
        var tiempoTotal = duracion.toString();
        // document.getElementById('comprobacion2').innerHTML =tiempoTotal; //a optimizar en el futuro
 
        var porcentajeFinal;
        var porcentaje = (tiempoActual/duracion)*100;
        var porcentajeHallado = porcentaje.toString();
        porcentajeFinal = (100-porcentaje)+porcentaje;

        if(audio1.ended){// cuando finaliza ó está en pausa.... detenemos el setInterval            
            document.getElementById('tiempox').innerHTML =  "100%";
        }
        
        else{
        document.getElementById('tiempox').innerHTML = Math.round(porcentajeHallado)  + "%"; 
        return porcentajeHallado;                
        }     
    }







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