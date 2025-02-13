<?php
include("results/telemetry_settings.php");
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
<meta charset="UTF-8" />
<link rel="shortcut icon" href="favicon.ico">
 <meta name="theme-color" content="#212121">
<script type="text/javascript" src="speedtest.js"></script>
<script type="text/javascript">
function I(i){return document.getElementById(i);}
//INITIALIZE SPEEDTEST
var s=new Speedtest();
s.setParameter("telemetry_level","basic");

var meterBk=/Trident.*rv:(\d+\.\d+)/i.test(navigator.userAgent)?"#424242":"#424242";
var dlColor="#00E676",
	ulColor="#00E5FF";
var progColorD="#00E676";
var progColorU="#00E5FF";

//CODE FOR GAUGES
function drawMeter(c,amount,bk,fg,progress,prog){
	var ctx=c.getContext("2d");
	var dp=window.devicePixelRatio||1;
	var cw=c.clientWidth*dp, ch=c.clientHeight*dp;
	var sizScale=ch*0.0055;
	if(c.width==cw&&c.height==ch){
		ctx.clearRect(0,0,cw,ch);
	}else{
		c.width=cw;
		c.height=ch;
	}
	ctx.beginPath();
	ctx.strokeStyle=bk;
	ctx.lineWidth=20*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,Math.PI*0.1);
	ctx.stroke();
	ctx.beginPath();
	ctx.strokeStyle=fg;
	ctx.lineWidth=22*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,amount*Math.PI*1.2-Math.PI*1.1);
	ctx.stroke();
	if(typeof progress !== "undefined"){
		ctx.fillStyle=prog;
		ctx.fillRect(c.width*0.3,c.height-16*sizScale,c.width*0.4*progress,8*sizScale);
	}

}
function mbpsToAmount(s){
	return 1-(1/(Math.pow(1.3,Math.sqrt(s))));
}
function format(d){
    d=Number(d);
    if(d<10) return d.toFixed(2);
    if(d<100) return d.toFixed(1);
    return d.toFixed(0);
}

//UI CODE
var uiData=null;
function startStop(){
	// Oculta recomendacoes
	var x = document.getElementById("recomend");
	if (x.style.display === "none") {
		x.style.display = "block";
	} else {
		x.style.display = "none";
	}

    if(s.getState()==3){
		//speedtest is running, abort
		s.abort();
		data=null;
		I("startStopBtn").className="";
		initUI();
	}else{
		//test is not running, begin
		I("startStopBtn").className="running";
		I("shareArea").style.display="none";
		s.onupdate=function(data){
            uiData=data;
		};
		
s.onend=function(aborted){
    I("startStopBtn").className="";
    updateUI(true);
    if(!aborted){
        // Preenche os valores no modal
		document.getElementById("modalIP").textContent = uiData.clientIp;
        document.getElementById("modalPing").textContent = format(uiData.pingStatus);
        document.getElementById("modalJitter").textContent = format(uiData.jitterStatus);
        document.getElementById("modalDownload").textContent = format(uiData.dlStatus);
        document.getElementById("modalUpload").textContent = format(uiData.ulStatus);
        
        // Adiciona um delay de 3 segundos (3000 milissegundos) antes de exibir o modal
        setTimeout(function() {
            document.getElementById("resultModal").style.display = "flex";
        }, 2000); // Altere o valor 3000 para o tempo de delay desejado (em milissegundos)
    }
};

		s.start();
	}
}
//this function reads the data sent back by the test and updates the UI
function updateUI(forced){
	if(!forced&&s.getState()!=3) return;
	if(uiData==null) return;
	var status=uiData.testState;
	I("ip").textContent=uiData.clientIp;
	I("dlText").textContent=(status==1&&uiData.dlStatus==0)?"...":format(uiData.dlStatus);
	drawMeter(I("dlMeter"),mbpsToAmount(Number(uiData.dlStatus*(status==1?oscillate():1))),meterBk,dlColor,Number(uiData.dlProgress),progColorD);
	I("ulText").textContent=(status==3&&uiData.ulStatus==0)?"...":format(uiData.ulStatus);
	drawMeter(I("ulMeter"),mbpsToAmount(Number(uiData.ulStatus*(status==3?oscillate():1))),meterBk,ulColor,Number(uiData.ulProgress),progColorU);
	I("pingText").textContent=format(uiData.pingStatus);
	I("jitText").textContent=format(uiData.jitterStatus);
}
function oscillate(){
	return 1+0.02*Math.sin(Date.now()/100);
}
//update the UI every frame
window.requestAnimationFrame=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.msRequestAnimationFrame||(function(callback,element){setTimeout(callback,1000/60);});
function frame(){
	requestAnimationFrame(frame);
	updateUI();
}
frame(); //start frame loop
//function to (re)initialize UI
function initUI(){
	drawMeter(I("dlMeter"),0,meterBk,dlColor,0);
	drawMeter(I("ulMeter"),0,meterBk,ulColor,0);
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
	I("ip").textContent="";
}

// Função para copiar os resultados
function copyResults() {
    let text = `
    🖥️ Resultado do Teste de Velocidade:
	🌎 IP: ${document.getElementById("modalIP").textContent}
    📍 Ping: ${document.getElementById("modalPing").textContent} ms
    🔄 Jitter: ${document.getElementById("modalJitter").textContent} ms
    ⬇️ Download: ${document.getElementById("modalDownload").textContent} Mbps
    ⬆️ Upload: ${document.getElementById("modalUpload").textContent} Mbps
    `;

    navigator.clipboard.writeText(text).then(() => {
        alert("Resultados copiados para a área de transferência!");
    }).catch(err => {
        console.error("Erro ao copiar:", err);
    });
}

function handleImage(action) {
    const modal = document.getElementById("modalContent"); // Seleciona o conteúdo do modal

    // Obtém a data e hora atual para o nome do arquivo
    const now = new Date();
    const formattedDate = now.getFullYear() + "-" + 
                          ("0" + (now.getMonth() + 1)).slice(-2) + "-" + 
                          ("0" + now.getDate()).slice(-2) + "_" + 
                          ("0" + now.getHours()).slice(-2) + "-" + 
                          ("0" + now.getMinutes()).slice(-2) + "-" + 
                          ("0" + now.getSeconds()).slice(-2);

    // Captura o modal como imagem
    html2canvas(modal, { scale: 2 }).then(canvas => {
        canvas.toBlob(function(blob) {
            const file = new File([blob], `teste_${formattedDate}.png`, { type: "image/png" });

            if (action === "save") {
                // Salva a imagem no dispositivo
                const link = document.createElement("a");
                link.href = URL.createObjectURL(file);
                link.download = `teste_${formattedDate}.png`; 
                link.click();
            } else if (action === "share") {
                // Compartilha a imagem via WhatsApp
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    navigator.share({
                        text: "Confira meu teste de velocidade!",
                        files: [file]
                    }).catch(err => console.error("Erro ao compartilhar:", err));
                } else {
                    alert("Seu dispositivo não suporta compartilhamento direto de imagens.");
                }
            }
        }, "image/png");
    }).catch(err => {
        console.error("Erro ao processar imagem:", err);
    });
}

// Função para fechar o modal ao clicar no "X"
function closeModal() {
    document.getElementById("resultModal").style.display = "none";
}

// Fechar modal ao clicar fora da área dele
window.onclick = function(event) {
    var modal = document.getElementById("resultModal");
    if (event.target === modal) {
        closeModal();
    }
};

</script>

<link rel="stylesheet" href="css/styles.css">
<title>Testador de Velocidade</title>
</head>
<body>
<div id="cabecalho">
<a href="<?php echo $LinkSite; ?>" target="_blank">
	<img id="logo" src="img/logo.png">
</a>	
</div>
<div id="testWrapper">
	<div id="startStopBtn" onclick="startStop()"></div><br/>
	<div id="test">
		<div class="testGroup">
			<div class="testArea2">
				<div class="testName">Ping</div>
				<div id="pingText" class="meterText" style="color:#FF9100"></div>
				<div class="unit">ms</div>
			</div>
			<div class="testArea2">
				<div class="testName">Jitter</div>
				<div id="jitText" class="meterText" style="color:#FF9100"></div>
				<div class="unit">ms</div>
			</div>
		</div>
		<div class="testGroup">
			<div class="testArea">
				<div class="testName">Download</div>
				<canvas id="dlMeter" class="meter"></canvas>
				<div id="dlText" class="meterText"></div>
				<div class="unit">Mbps</div>
			</div>
			<div class="testArea">
				<div class="testName">Upload</div>
				<canvas id="ulMeter" class="meter"></canvas>
				<div id="ulText" class="meterText"></div>
				<div class="unit">Mbps</div>
			</div>
		</div>

		<div id="recomend">
			<b>Recomendações</b> <br />
			&raquo; Faça o teste se possível por meio de um cabo de rede. <br />
			&raquo; Peça para outras pessoas que desliguem seu dispositivo.<br />
			&raquo; Feche todos os programas e atualizações.<br />
			&raquo; Repita o teste mais de uma vez.
		</div>
		<div id="ipArea">
			<span id="ip"></span>
		</div>
		<div id="shareArea" style="display:none">			
			<!--h3>Compartilhar URL do resultado:</h3-->
			<input type="text" value="" id="resultsURL" readonly="readonly" onclick="this.select();this.focus();this.select();document.execCommand('copy');alert('Link copiado')"/>
			<img src="" id="resultsImg" />	
		</div>		
	</div>
</div>
<script type="text/javascript">setTimeout(function(){initUI()},100);</script>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> - <?php echo $NomeProvedor; ?></p>
    </footer>
<!-- Modal -->
<div id="resultModal" class="modal">
  <div class="modal-content" id="modalContent">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Resultado do Teste de Velocidade</h2>
	<p><b>IP:</b> <span id="modalIP"></span></p>
    <p><b>Ping:</b> <span id="modalPing"></span> ms</p>
    <p><b>Jitter:</b> <span id="modalJitter"></span> ms</p>
    <p><b>Download:</b> <span id="modalDownload"></span> Mbps</p>
    <p><b>Upload:</b> <span id="modalUpload"></span> Mbps</p>
	<button onclick="handleImage('save')">🖼️ Salvar como Imagem</button>
	<button onclick="handleImage('share')">
		<img src="img/wpp.png" alt="WhatsApp" style="width: 20px; margin-right: 8px;"> Enviar no WhatsApp
	</button>
	<button onclick="copyResults()">📋 Copiar</button>
	  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<div id="selo-container">
    <img src="img/top-v6.png" alt="Selo" id="selo">
</div>

</body>
</html>