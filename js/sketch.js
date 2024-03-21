var song;
var fft;
var particles = []; //para ocntener a los objetos particula que creemos

function preload(){
  song = loadSound('../audios/Sugar Red.mp3');
 // song.play();
}
/*function preload(){
  var audioElement = document.getElementById('audio');
  var audioSource = audioElement.getElementsByTagName('source')[0];
  var audioSrc = audioSource.src;
  song = loadSound(audioSrc);
} */

function setup() {
  let lienzo = createCanvas(windowWidth, windowHeight);
 // lienzo.parent('miLienzo');
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
    song.jump(0);
   // song.pause();
    noLoop();
  } else{
    song.play();
    loop();
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
