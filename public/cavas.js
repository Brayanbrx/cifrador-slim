// wave.js
window.onload = () => {
  const canvas = document.getElementById("canvas");
  const gl     = canvas.getContext("webgl");
  if (!gl) {
    console.error("WebGL no disponible");
    return;
  }

  // Vertex shader (sin cambios)
  const vertexSrc = `
    attribute vec2 position;
    void main() {
      gl_Position = vec4(position, 0.0, 1.0);
    }
  `;

  // Fragment shader: animación más lenta tanto en color como en onda
  const fragmentSrc = `
    precision highp float;
    uniform vec2 resolution;
    uniform float time;

    // HSV → RGB
    vec3 hsv2rgb(vec3 c) {
      vec3 p = abs(mod(c.x*6.0 + vec3(0.,4.,2.),6.0) - 3.0) - 1.0;
      return c.z * mix(vec3(1.0), clamp(p,0.0,1.0), c.y);
    }

    // Glow de una onda
    float waveGlow(vec2 pos, float radius, float intensity,
                   float speed, float amp,
                   float freq, float phase) {
      // Reducimos el ritmo de la onda multiplicando time por 0.3
      float t = time * 0.3;
      float d = abs(pos.y + amp * sin(speed * t + pos.x * freq + phase));
      d = 1.0 / max(d, 0.0001);
      d *= radius;
      return pow(d, intensity);
    }

    void main() {
      vec2 uv = gl_FragCoord.xy / resolution;
      float aspect = resolution.x / resolution.y;
      vec2 pos = vec2(0.5) - uv;
      pos.y /= aspect;

      // Parámetros con amplitudes grandes
      float g1 = waveGlow(pos, 0.008, 0.6,  2.0, 0.06, 2.5, 0.0);
      float g2 = waveGlow(pos, 0.007, 0.5, -2.0, 0.07, 3.0, 1.0);
      float g3 = waveGlow(pos, 0.009, 0.4,  3.0, 0.05, 2.0, 2.0);
      float g4 = waveGlow(pos, 0.006, 0.5, -1.5, 0.08, 3.5, 3.0);
      float g5 = waveGlow(pos, 0.008, 0.3,  1.0, 0.06, 2.2, 4.0);
      float g6 = waveGlow(pos, 0.007, 0.45, 2.5, 0.05, 2.8, 5.0);
      float g7 = waveGlow(pos, 0.006, 0.4, -3.5, 0.07, 3.2, 6.0);
      float g8 = waveGlow(pos, 0.009, 0.35, 1.5, 0.06, 2.0, 7.0);

      // Máscaras alrededor de cada línea con t lento
      float t = time * 0.3;
      float m1 = smoothstep(0.02,0.008,abs(pos.y + 0.06 * sin(t*1.0 + pos.x*2.5 + 0.0)));
      float m2 = smoothstep(0.02,0.008,abs(pos.y + 0.07 * sin(t*1.2 + pos.x*3.0 + 1.0)));
      float m3 = smoothstep(0.02,0.008,abs(pos.y + 0.05 * sin(t*1.4 + pos.x*2.0 + 2.0)));
      float m4 = smoothstep(0.02,0.008,abs(pos.y + 0.08 * sin(t*1.6 + pos.x*3.5 + 3.0)));
      float m5 = smoothstep(0.02,0.008,abs(pos.y + 0.06 * sin(t*1.8 + pos.x*2.2 + 4.0)));
      float m6 = smoothstep(0.02,0.008,abs(pos.y + 0.05 * sin(t*2.0 + pos.x*2.8 + 5.0)));
      float m7 = smoothstep(0.02,0.008,abs(pos.y + 0.07 * sin(t*2.2 + pos.x*3.2 + 6.0)));
      float m8 = smoothstep(0.02,0.008,abs(pos.y + 0.06 * sin(t*2.4 + pos.x*2.0 + 7.0)));

      // Hue aún más lento: time * 0.02
      float hue = mod(time * 0.02, 1.0);
      vec3 rgb = hsv2rgb(vec3(hue, 1.0, 1.0));

      // Combinar
      vec3 col = 
        g1*rgb*m1 +
        g2*rgb*m2 +
        g3*rgb*m3 +
        g4*rgb*m4 +
        g5*rgb*m5 +
        g6*rgb*m6 +
        g7*rgb*m7 +
        g8*rgb*m8 ;

      // Tonemapping + gamma
      col = 1.0 - exp(-col);
      col = pow(col, vec3(0.4545));

      gl_FragColor = vec4(col,1.0);
    }
  `;

  // Compilar y linkear el programa
  function compile(src, type) {
    const s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
      console.error("Shader error:", gl.getShaderInfoLog(s));
    }
    return s;
  }

  const vSh = compile(vertexSrc, gl.VERTEX_SHADER),
        fSh = compile(fragmentSrc, gl.FRAGMENT_SHADER),
        prog=gl.createProgram();
  gl.attachShader(prog, vSh);
  gl.attachShader(prog, fSh);
  gl.linkProgram(prog);
  gl.useProgram(prog);

  // Localizar atributos y uniforms
  const aPos  = gl.getAttribLocation(prog, "position");
  const uRes  = gl.getUniformLocation(prog, "resolution");
  const uTime = gl.getUniformLocation(prog, "time");

  // Fullscreen quad
  const quad = new Float32Array([-1,1, -1,-1, 1,1, 1,-1]);
  const buf  = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(gl.ARRAY_BUFFER, quad, gl.STATIC_DRAW);
  gl.enableVertexAttribArray(aPos);
  gl.vertexAttribPointer(aPos,2,gl.FLOAT,false,0,0);

  // Resize
  function resize(){
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    gl.viewport(0,0,canvas.width,canvas.height);
    gl.uniform2f(uRes,canvas.width,canvas.height);
  }
  window.addEventListener("resize",resize);
  resize();

  // Bucle de animación
  let last = performance.now(), t=0;
  (function loop(now){
    gl.clearColor(0,0,0,1);
    gl.clear(gl.COLOR_BUFFER_BIT);
    t += (now - last)/1000;
    last = now;
    gl.uniform1f(uTime,t);
    gl.drawArrays(gl.TRIANGLE_STRIP,0,4);
    requestAnimationFrame(loop);
  })(last);
};
