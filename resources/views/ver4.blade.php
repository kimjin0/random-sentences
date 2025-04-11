<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>랜덤 영어 생성기</title>
<link rel="stylesheet" href="/css/common.css?v=20250327">
<style>
  .fs40{
    font-size:40px;
  }
  .fs30{
    font-size:30px;
  }
  .mt15{
    margin-top:15px
  }
  .fw-normal{
    font-weight: normal;
  }
  .fblue{
    color:#0000FF;
  }
  .fgreen{
    color:#008000;
  }
  .speak{
    font-size: 33px;
    border: 1px solid #ccc;
    border-radius: 14px;
    padding: 5px 10px;
    margin-left: 10px;
  }
  /* 메뉴스타일 */
  .slide {
    width: 100%;
    padding: 10px 0;
  }

	.list {
		list-style: none;
		margin: 0;
		padding: 0;
		display: flex;
		justify-content: center;
		gap: 20px;
		font-size: 50px;
	}

	.list li {
		position: relative;
		padding-bottom: 10px; /* 아래 공간 확보 */
	}

	.list a {
		text-decoration: none;
		padding: 10px 20px;
		color: #333;
		font-weight: bold;
		transition: all 0.3s;
	}

	.list li.active::after {
		content: "";
		position: absolute;
		left: 0;
		bottom: 0;
		width: 100%;
		height: 3px;
		background-color: #3498db; /* 원하는 밑줄 색 */
		border-radius: 2px;
	}
</style>
</head>
<body>
<div class="container">
  <div class="slide">
    <ul class="list">
      <li >
          <a href="/">3월</a>
      </li>
      <li class="active">
          <a href="/ver4">4월</a>
      </li>
    </ul>
  </div>
  </div>  <div class="voice-control">
    <label for="rate">말하기 속도:</label>
    <input type="range" id="rate" min="0.5" max="1.5" step="0.05">
    <span id="rate-value">0.85</span>
  </div>
  <div class="control-group">
    <!-- <label for="group">그룹 선택:</label> -->
    <select id="group" onchange="updateSelectedGroup(this.value)">
      <option value="all">전체</option>
      <option value="do_you_wanna">do you wanna / ~하고 싶어?, ~할래?</option>

    </select>
  </div>
  <div class="control-group mt15">
    <button onclick="sentenceView()">랜덤 문장 보기</button>
    <button class="listen" onclick="sentenceListen()">랜덤 문장 듣기</button>
  </div>
  <div id="card" style="display: none">
    <p class="korean" id="korean-text"></p>
    <button onclick="englishShow()">👉 영어 보기</button>
    <div class="english" id="english-text">
      <div ></div>
    </div>
  </div>
  <div id="cardListen" style="display: none">
    <p class="listen" id="english-listen">👉 듣기</p>
    <div class="english" id="explanation">
      <div ></div>
    </div>
  </div>
</div>

<script>
  let selectedGroup = 'all';
  let sentences = {};
  let usedSentences = new Set();
  let currentSentence = null;
  let countdown = 10;
  let showAnswer = false;
  let showSentence = false;
  let speechRate = 0.85;
  let timer = null;
  let voices = [];

  document.getElementById('rate').value = speechRate;
  document.getElementById('rate').addEventListener('input', function() {
    speechRate = this.value;
    document.getElementById('rate-value').textContent = speechRate;
  });

  window.speechSynthesis.onvoiceschanged = function() {
    voices = this.getVoices();
  };

  fetch('/api/ver4.json')
    .then(response => response.json())
    .then(data => {
      sentences = data;
    });

  function updateSelectedGroup(value) {
    selectedGroup = value;
  }
  const $cardListen = document.getElementById('cardListen');
  const $card = document.getElementById('card');

  function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/`/g, "&#x60;");
  }
  //랜덤 문장 듣기
  function sentenceListen() {
    document.getElementById('explanation').style.display = 'none';
    showSentence = false;

    let pool = selectedGroup === 'all' ? [].concat(...Object.values(sentences)) : sentences[selectedGroup] || [];
    const filteredPool = pool.filter(s => !usedSentences.has(s.ko));

    if (filteredPool.length === 0) {
      alert('All sentences from this group have been shown. Resetting...');
      usedSentences.clear();
      return;
    }

    const random = filteredPool[Math.floor(Math.random() * filteredPool.length)];
    currentSentence = random;
    usedSentences.add(random.ko);

    $cardListen.style.display = 'block';
    $card.style.display = 'none';
    const $englishListen = document.getElementById('english-listen');
    let html = `<button class="replay-btn" onclick='fnSpeak("${escapeHtml(currentSentence.en)}");'>재생</button><button onclick="sentenceShow()">👉 문장 보기</button>`;
    $englishListen.innerHTML = html;
  }

  function sentenceView() {
    document.getElementById('english-text').style.display = 'none';
    showAnswer = false;

    let pool = selectedGroup === 'all' ? [].concat(...Object.values(sentences)) : sentences[selectedGroup] || [];
    const filteredPool = pool.filter(s => !usedSentences.has(s.ko));

    if (filteredPool.length === 0) {
      alert('All sentences from this group have been shown. Resetting...');
      usedSentences.clear();
      return;
    }

    const random = filteredPool[Math.floor(Math.random() * filteredPool.length)];
    currentSentence = random;
    usedSentences.add(random.ko);
    document.getElementById('korean-text').textContent = random.ko;
    $card.style.display = 'block';
    $cardListen.style.display = 'none';
  }

  // 랜덤 문장 듣기 > 문장보기
  function sentenceShow() {
    const explanationElement = document.getElementById('explanation');

    if (!showSentence) {
      showSentence = true;
      let html = `<div class="fs40 mt15">${currentSentence.ko}<br>${currentSentence.en}</div>`;

      if (currentSentence.description) {
        html += `<hr><div class="fw-normal mt15 fs30">📝 ${currentSentence.description}</div>`;
      }
      if (currentSentence.talk) {
        html += `<hr><div class="fw-normal mt15 fs30"><strong>Situation:</strong> ${currentSentence.talk.situation}</div>`;
        html += `<ul class="fs30">`;
        currentSentence.talk.dialogue.forEach(function(dialog) {
          html += `
            <li>
              <span class="fw-normal">${dialog.speaker}:</span><br>
              <span class="fgreen">${dialog.ko}</span><br>
              <span class="fblue">${dialog.en}</span>
              <span class="replay-btn speak" onclick='fnSpeak("${escapeHtml(dialog.en)}")'>🔊</span>
            </li>
            `;
        });
        html += `</ul>`;
      }

      explanationElement.querySelector('div').innerHTML = html;
      explanationElement.style.display = 'block';
    } else {
      explanationElement.style.display = 'none';
      showSentence = false;
    }
  }

  // 랜던 문장 보기 > 영어보기
  function englishShow() {
    const englishTextElement = document.getElementById('english-text');

    if (!showAnswer) {
      showAnswer = true;
      let html = `<div class="fs40 mt15">${currentSentence.en} <span class="replay-btn speak" onclick='fnSpeak("${escapeHtml(currentSentence.en)}")'>🔊</span></div>`;

      if (currentSentence.description) {
        html += `<hr><div class="fw-normal mt15 fs30">📝 ${currentSentence.description}</div>`;
      }
      if (currentSentence.talk) {
        html += `<hr><div class="fw-normal mt15 fs30"><strong>Situation:</strong> ${currentSentence.talk.situation}</div>`;
        html += `<ul class="fs30">`;
        currentSentence.talk.dialogue.forEach(function(dialog) {
          html += `
            <li>
              <span class="fw-normal">${dialog.speaker}:</span><br>
              <span class="fgreen">${dialog.ko}</span><br>
              <span class="fblue">${dialog.en}</span>
              <span class="replay-btn speak" onclick='fnSpeak("${escapeHtml(dialog.en)}")'>🔊</span>
            </li>
          `;
        });
        html += `</ul>`;
      }

      englishTextElement.querySelector('div').innerHTML = html;
      englishTextElement.style.display = 'block';
    } else {
      englishTextElement.style.display = 'none';
      showAnswer = false;
    }
  }

  function fnSpeak(enSentence) {
    const utterance = new SpeechSynthesisUtterance(enSentence);
    utterance.lang = 'en-US';
    utterance.rate = speechRate;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    const femaleVoice = voices.find(v => v.lang.startsWith('en') && /female|alex/i.test(v.name));
    utterance.voice = femaleVoice || voices.find(v => v.lang.startsWith('en') && !/male/i.test(v.name));

    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
  }
</script>
</body>
</html>
