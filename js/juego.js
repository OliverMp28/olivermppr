var time = new Date();
var deltaTime = 0;

//p5
//var song;
var fft;
var particles = []; //para ocntener a los objetos particula que creemos



//aca da la señal al cargar la pagina
/*if (document.readyState === "complete" || document.readyState === "interactive"){
    setTimeout(AudioLoad,1);
    
}else{
    document.addEventListener("DOMContentLoaded", AudioLoad);
}*/

document.addEventListener('DOMContentLoaded', function() {
    AudioLoad();
});

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
var mensajeCarga = document.getElementById("mensaje-carga");
var audio1;
audio1 = document.getElementById("audio");
var audioFinal = document.getElementById("final");
var audioPi = document.getElementById("pipipi");

var areaTouch= document.getElementById("cuerpo");

var nuevaCancion;
var caja1 = document.querySelector("#juegazo");
var cajaLista = document.querySelector(".lista");
var play = document.querySelector(".contenedor-boton");
const nombreCancion =document.getElementById("nombreJuego");



botonPlay.addEventListener("click", Iniciar);

function Iniciar(){
    if (song.isLoaded()) {
        AudioOn();
        play.style.display ="none";
      }

}

var repetir = document.getElementById("repetir");

repetir.addEventListener("click", Reiniciar);

function Reiniciar(){
    for(let i = 0; i<=obstaculos.length; i++){
        for(let i = obstaculos.length - 1; i >= 0; i--){
            contenedor.removeChild(obstaculos[i]);
            obstaculos.splice(i,1);
        }
        console.log(obstaculos);
        gameOver.style.display = "none";
        tryAgain.style.display = "none";
        youWin.style.display = "none";
        x=false;
        audio1.currentTime=0;
        
        //console.log(song.currentTime()) //reinicia el p5
        audio1.play();
        song.play(); //esto es del p5
        song.jump(0);
        JuegoPlay();
        score = 0; 
        gameVel = 1;
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
            song.pause();
            JuegoStop();
        });
    
        window.addEventListener("focus", function() {
          if(!x){
            audio1.play();
            song.play();
            JuegoPlay();
          }

        });
          
     }
    else if(x==true){
        parado=true;
        audio1.pause();
        song.pause();
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

function incrementarVelocidad(velocidadObjetivo) { //esta funcion aumenta la velocidad gradualmente para que el cambio no sea de golpe
    if (gameVel < velocidadObjetivo) {
        gameVel += 0.01; // ajusta este valor para cambiar la rapidez del incremento
        setTimeout(() => incrementarVelocidad(velocidadObjetivo), 20); // ajusta este valor para cambiar la frecuencia del incremento
    }else{
        gameVel = velocidadObjetivo;
    }
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
            incrementarVelocidad(0.7);
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#aa8c8c";
        }
        else if(score >= 5 && score < 15){
            incrementarVelocidad(1.2);
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#a6a6ad";
        }else if(score >= 15 && score < 25) {
            incrementarVelocidad(1.4);
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#c06253";
        } else if(score >= 25) {
            incrementarVelocidad(2.1);
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#4d455f";
        }
        suelo.style.animationDuration = (3/gameVel)+"s";
        console.log(gameVel);
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
            incrementarVelocidad(1);
           /* contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche"); */
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#aa8c8c";
        }
        else if(score >= 5 && score < 15){
            incrementarVelocidad(1.5);
           /* contenedor.classList.remove("tarde");
            contenedor.classList.remove("noche");
            contenedor.classList.add("mediodia");*/
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#a6a6ad";
        }else if(score >= 15 && score < 25) {
            incrementarVelocidad(2);
           /* contenedor.classList.remove("mediodia");
            contenedor.classList.remove("noche");
            contenedor.classList.add("tarde");*/
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#c06253";
        } else if(score >= 25) {
            incrementarVelocidad(3);
           /* contenedor.classList.remove("mediodia");
            contenedor.classList.remove("tarde");
            contenedor.classList.add("noche");*/
            contenedor.style.transition = "background-color 2s";
            contenedor.style.backgroundColor = "#4d455f";
        }
        suelo.style.animationDuration = (3/gameVel)+"s";
        console.log(gameVel);
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
    var cancion0 = document.getElementById("cancion0");
    var ruta = window.srcCancionElegida;
    cancion0.src = `../audios/${ruta}`;
    audio1.volume = 0;
 
    nombreCancion.innerHTML = window.nombreCancionElegida;

     // Muestra el mensaje de carga por defecto
   /*  mensajeCarga.style.display = "block";

     // Cuando la canción se ha cargado completamente
     cancion0.oncanplaythrough = function() {
         mensajeCarga.style.display = "none"; // Oculta el mensaje de carga
         botonPlay.style.display = "block"; // Muestra el botón de "Play"
     };

         // Escucha el evento timeupdate para comprobar continuamente si la canción se está reproduciendo
    cancion0.addEventListener("timeupdate", function() {
        if (!song.isPlaying() && cancion0.currentTime > 0) {
            mensajeCarga.style.display = "none"; // Oculta el mensaje de carga
            botonPlay.style.display = "block"; // Muestra el botón de "Play"
        }
    }); */
 
    audio1.load();
}

//esto es del p5, esta predeterminadoa  ejecucion
function preload(){
    var audioSrc = cancion0.src
    song = loadSound(audioSrc, soundLoaded);
  }

function soundLoaded() {
    console.log("El audio se ha cargado completamente y está listo para reproducirse");

     mensajeCarga.style.display = "none";

     botonPlay.style.display = "block"; // Muestra el botón de "Play"
}
  
function AudioOn(){
       audio1.play();
       song.play();
}

function AudioPause(){
    audio1.pause();
    song.pause();
};

function AudioOff(){
        audio1.pause();    
        song.pause();              
}

function AudioFinalOn(){
    audioFinal.load();
    audioFinal.oncanplaythrough = function() {
        audioFinal.play();
    }
}

function AudioPipipi(){
    audioPi.load();
    audioPi.oncanplaythrough = function() {
        audioPi.play();
    }
}

function Terminar(){
    x=true;
    GuardarProgreso();
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


var porcentaje = 0;

    function verifica_fin(){
        var tiempoActual = audio1.currentTime; // recuperamos el tiempo actual de reproducción y lo redondeamos a un entero
        var segs = tiempoActual.toString(); // convertimos el tiempo actual a una cadena para poder formatearlo en hh:mm:ss
        // mandamos el tiempo actual a un div en pantalla
        // document.getElementById('comprobacion1').innerHTML = segs;

        var duracion = audio1.duration;
        var tiempoTotal = duracion.toString();
        // document.getElementById('comprobacion2').innerHTML =tiempoTotal; //a optimizar en el futuro
 
        var porcentajeFinal;
        porcentaje = (tiempoActual/duracion)*100;
        var porcentajeHallado = porcentaje.toString();

        if(audio1.ended){// cuando finaliza ó está en pausa.... detenemos el setInterval            
            document.getElementById('tiempox').innerHTML =  "100%";
        }
        
        else{
        document.getElementById('tiempox').innerHTML = Math.round(porcentajeHallado)  + "%"; 
        return porcentajeHallado;                
        }     
    }


//guardar puntos a base de datos
//esta era el antigua funciona para guardar los datos que llevaba al gestionar_progreso.php pero lo cambie
/*function GuardarProgreso(){
    document.getElementById('inputPorcentaje').value = Math.round(porcentaje);
    document.getElementById('inputPts').value = score;
    document.getElementById('idCancionCargar').value = window.idCancionElegida;
    document.getElementById('formularioProgreso').submit();
}*/

function GuardarProgreso(){
    var formData = new FormData(document.getElementById('formularioProgreso'));
    formData.append('inputPorcentaje', Math.round(porcentaje));
    formData.append('inputPts', score);
    formData.append('idCancionCargar', window.idCancionElegida);

    fetch('../controladores_php/gestionar_progreso.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Aquí puedes manejar la respuesta del servidor
        console.log(data);
    })
    .catch((error) => {
        console.error('Error:', error);
    });
}








//-------------- Gestionando parte del P5 -----------------


/*function preload(){
  song = loadSound('../audios/Sugar Red.mp3');
 // song.play();
}*/


function setup() {
  let lienzo = createCanvas(windowWidth, windowHeight);
  lienzo.parent('miLienzo');
  angleMode(DEGREES); //cambiar el modo de angulo de radianes a grados
  fft = new p5.FFT(0.3);
}

function draw() {
  background(0);
  stroke(255); //esto es el color de la onda
  strokeWeight(3); //esto es para gestionar el grosor
  noFill(); //esto es para eliminar el color de relleno de la onda

  translate(width / 2, height / 2); //esto es para centar

  fft.analyze(); //llamo a este metodo para que funcione getEnergy
  amp = fft.getEnergy(20, 200); //con esto obtengo la energia de frecuencia, y le doy un rango

  var wave = fft.waveform();


  for (var t = -1; t <= 1; t += 2){
    beginShape();
    for(var i = 0; i <= 180; i += 0.5){
      var index = floor(map(i, 0, 180, 0, wave.length - 1));

      var r = map(wave[index], -1, 1, 75, 175);

      var x = r * sin(i) * t;
      var y = r * cos(i);
      vertex(x ,y);
    }
    endShape(); //esto al igual que el beginShape es para conectar la onda de sonido 
    //por una linea, asi no de ven como puntos sino mas bien como una sola linea que se mueve con la cancion
  }

  if (song.isPlaying()) {
    var p = new Particle(); //creamos el objeto de particula
    particles.push(p);

    for(var i = particles.length - 1; i >= 0; i--){//aca mostramos las particulas llamando al metodo show() de la clase Particles
      if (!particles[i].edges()){
        particles[i].update(amp > 230);
        particles[i].show();
        //console.log(amp);
      } else{
        particles.splice(i, 1);
      }
    }
  }
}

function mouseClicked(){
  if(song.isPlaying()){
  //  song.pause();
 //   noLoop();
  } else{
   // song.play();
   // loop();
  }
}

class Particle { //creamos la clase particula, para crear objetos de tipo particula
  constructor(){
    this.pos = p5.Vector.random2D().mult(125) //esto es para posicionar las particulas en el perimetro del circulo
    this.vel = createVector(0,0); //crea la velocidad en 0
    this.acc = this.pos.copy().mult(random(0.0001, 0.00001)) // esta es la aceleracion

    this.w = random(3, 5); //el ancho aleatorio

    this.color = [random(10, 255), random(10, 255), random(10, 255),];
  }
  update(cond) {//para actualizar la posicion de las particulas
    this.vel.add(this.acc); //la aceleracion se agrega a la velocidad
    this.pos.add(this.vel); //la velocidad se agrega a la posicion
    if (cond){
      this.pos.add(this.vel);
      this.pos.add(this.vel);
      this.pos.add(this.vel);
    }
  }
  edges(){
    if(this.pos.x < -width / 2 || this.pos.x > width / 2 || this.pos.y < -height / 2 || this.pos.y > height / 2){
      return true;
    } else{
      return false;
    }
  }
  show(){ //este metodo mostrara la particula en el lienzo
    noStroke();
    fill(this.color); //fill=0llenar, llena con un color el bojeto que estamos creando
    ellipse(this.pos.x, this.pos.y, this.w)
  }
}