<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>7 Colors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div id="modal_welcome" class="modal" tabindex="1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">7 Цветов</h5>
            </div>
            <div class="modal-body">
                Данная игра была сделана с целью участия в цифровой олимпиаде "<a href="https://www.volga-it.org/">Волга-IT’XXI</a>"
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="switchModal('modal_connect')">Присоедениться</button>
                <button type="button" class="btn btn-success" onclick="switchModal('modal_newgame')">Создать игру</button>
            </div>
        </div>
    </div>
</div>

<div id="modal_newgame" class="modal" tabindex="1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая игра</h5>
            </div>
            <div class="modal-body">
                <div class="form-floating mb-3">
                    <input class="form-control" id="input_width" placeholder="5-99 (Нечетное число)">
                    <label for="floatingInput">Ширина</label>
                </div>
                <div class="form-floating">
                    <input class="form-control" id="input_height" placeholder="5-99 (Нечетное число)">
                    <label for="floatingPassword">Высота</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="switchModal('modal_welcome')">Назад</button>
                <button type="button" class="btn btn-primary" onclick="createGame(input_width.value, input_height.value)">Создать игру</button>
            </div>
        </div>
    </div>
</div>

<div id="modal_connect" class="modal" tabindex="1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Присоедениться к игре</h5>
            </div>
            <div class="modal-body">
                <div class="form-floating mb-3">
                    <input class="form-control" id="input_uuid" placeholder="5-99 (Нечетное число)">
                    <label for="floatingInput">UUID игры</label>
                </div>
                <div class="form-check">
                    <input name="player_select" value="1" class="form-check-input" type="radio">
                    <label class="form-check-label">Игрок 1</label>
                </div>
                <div class="form-check">
                    <input name="player_select" value="2" class="form-check-input" type="radio">
                    <label class="form-check-label">Игрок 2</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="switchModal('modal_welcome')">Назад</button>
                <button type="button" class="btn btn-primary" onclick="connectGame(input_uuid.value, getPlayer())">Присоедениться</button>
            </div>
        </div>
    </div>
</div>

<div id="game" class="modal"> <!-- Да, это костыль, отстаньте -->
    <div id="color_buttons">
    </div>
    <h2 class="text-center"><span id="gameUUID"></span> | <span id="player1">0%</span> / <span id="player2">0%</span></h2>
    <canvas id="canvas"></canvas>
</div>
<script>
    const canvas = document.getElementById("canvas");
    const gameUUID = document.getElementById("gameUUID");
    const p1percent = document.getElementById("player1");
    const p2percent = document.getElementById("player2");
    const ctx = canvas.getContext("2d");
    const input_width = document.getElementById("input_width");
    const input_height = document.getElementById("input_height");
    const input_uuid = document.getElementById("input_uuid");
    const color_buttons = document.getElementById("color_buttons");


    function getPlayer(){
        const radios = document.querySelectorAll("input[name='player_select']");
        for (const radio of radios) {
            console.log(radio)
            if (radio.checked) {
                return radio.value;
            }
        }
        return 0;
    }

    const modals = document.querySelectorAll(".modal");
    function switchModal(modalName = null){
        for(const modal of modals){
            modal.style = "display: none";
            if(modal.id === modalName) modal.style = "display: block";
        }
    }
    switchModal("modal_welcome");

    class Color{
        constructor(color, name) {
            this.color = color;
            this.name = name;
        }
    }

    const colors = [
        new Color("rgb(0,0,200)", "blue"),
        new Color("rgb(0,200,0)", "green"),
        new Color("rgb(0,200,200)", "cyan"),
        new Color("rgb(200,0,0)", "red"),
        new Color("rgb(200,0,200)", "magenta"),
        new Color("rgb(200,200,0)", "yellow"),
        new Color("rgb(200,200,200)", "white")
    ];

    class GameSession{
        uuid;
        player;

        step(color){
            const xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "PUT", `/game/${this.uuid}?playerId=${this.player}&color=${color}`, false );
            xmlHttp.send( null );
        }

        update(){
            const xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "GET", `/game/${this.uuid}`, false );
            xmlHttp.send( null );
            const data = xmlHttp.responseText;
            this.data = JSON.parse(data);
        }

        constructor(uuid, player) {
            this.uuid = uuid;
            this.player = player;
        }
    }

    let gameSession

    for(const color of colors){
        let button = document.createElement("button");
        button.classList.add("btn");
        button.classList.add("btn-primary");
        button.style = `background-color: ${color.color}`;
        button.onclick = function(){
            gameSession.step(color.name);
        };
        color_buttons.append(button);
    }

    function createGame(width, height){
        const xmlHttp = new XMLHttpRequest();
        xmlHttp.open( "POST", '/game', false );
        xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlHttp.send( `width=${width}&height=${height}` );
        const data = xmlHttp.responseText;
        const {game} = JSON.parse(data);
        gameSession = new GameSession(game, 1);
        switchModal("game");
    }

    function connectGame(uuid, player){
        gameSession = new GameSession(uuid, player);
        switchModal("game");
    }

    function drawRhombus(x,y,color,player=0,size=40){
        color = colors.find(element=>{
            if(element.name === color) return true;
        })
        ctx.fillStyle = color.color;
        ctx.beginPath();
        ctx.moveTo(x*size + size/2, y*size);
        ctx.lineTo(x*size + size  , y*size + size/2);
        ctx.lineTo(x*size + size/2, y*size + size);
        ctx.lineTo(x*size         , y*size + size/2);
        ctx.fill();
        ctx.fillStyle = "white";
        if(player !== 0) ctx.fillText(player.toString(),x*size + 10,y*size + size/2);
    }

    function updateScreen() {
        if(gameSession) {
            let x = 0;
            let y = 0;

            gameSession.update();
            const responce = gameSession.data;

            const currentPlayerId = responce.currentPlayerId === gameSession.player;
            const {width, height, cells} = responce.field;

            canvas.width = width * 40;
            canvas.height = height * 40;

            const countCells = cells.length;
            let p1cells = 0;
            let p2cells = 0;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (const cell of cells) {
                if (cell.playerId == 1) p1cells++;
                if (cell.playerId == 2) p2cells++;

                drawRhombus(x, y, cell.color, cell.playerId);
                x++;

                if (x > (width - 1)) {
                    x = 0;
                    y += 0.5;
                    if (y % 1 === 0.5) {
                        x += 0.5;
                    }
                }
            }

            p1percent.innerText = `${Math.floor((p1cells * 100) / countCells)}%`;
            p2percent.innerText = `${Math.floor((p2cells * 100) / countCells)}%`;
            gameUUID.innerText = gameSession.uuid;
        }
    }

    setInterval(()=>{
        updateScreen()
    }, 500);

</script>
</body>
</html>
