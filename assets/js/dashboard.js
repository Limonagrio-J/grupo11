document.addEventListener('DOMContentLoaded',()=>{const c=document.getElementById('chartCursos');if(c){try{dibujarGraficoBarras(c,JSON.parse(c.dataset.cursos||'[]'))}catch(e){console.error(e)}}});
