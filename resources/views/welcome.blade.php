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
</style>
</head>
<body>
<div class="container">
  <h2>랜덤 영어 생성기</h2>
  <div class="voice-control">
    <label for="rate">말하기 속도:</label>
    <input type="range" id="rate" min="0.5" max="1.5" step="0.05">
    <span id="rate-value">0.85</span>
  </div>
  <div class="control-group">
    <!-- <label for="group">그룹 선택:</label> -->
    <select id="group" onchange="updateSelectedGroup(this.value)">
      <option value="all">전체</option>
      <option value="LetMe">Let me + 동사원형 / 내가 ~할게, 나 ~하게 해줘</option>
      <option value="DidYou">Did you ~? / ~했어요?</option>
      <option value="ShallWe">Shall we ~? / 우리 ~할까?</option>
      <option value="HowAbout">How about + 동명사(~ing)? / ~하는게 어때?</option>
      <option value="itCosts">It costs~ / ~하는 데 돈이 든다</option>
      <option value="areYouGoingTo">are you going to ~?  / ~할 거예요?</option>
      <option value="ImReadyTo">I'm ready to ~ / ~할 준비가 됐어, 이제 ~할 수 있어</option>
      <option value="itTakes">It takes ~ / ~하는 데 (시간)이 걸리다</option>
      <option value="someThings">something, somewhere, someone / 무언가, 어떤 장소, 누군가</option>
      <option value="ImHereTo">i'm here to ~ / ~하러 (여기)왔어요</option>
      <option value="ImJustGoingTo">I think I'm just going to~  내 생각에는 그냥 ~할 것 같아 / 나는 그냥 ~하려고 해</option>
      <option value="imJustAboutTo">I'm just about to~ / 막 ~하려던 참이야</option>
      <option value="itsGetting">It's getting / 점점~해지고 있어</option>
      <option value="itsTimeTo">It's time to~ / ~할 시간이야</option>
      <option value="isIt">is it / ~인가요?</option>
      <option value="imTryingTo">I'm trying to ~ / ~하려고 노력 중이야</option>
      <option value="thereIsAre">There is~, There are~ / ~이 있다, ~들이 있다</option>
      <option value="iJustWantedTo">I just wanted to / 나는 단지 ~ 하고 싶었어</option>
      <option value="doIHaveTo">Do I have to~? / 내가 ~해야 하나요?</option>
      <option value="doYouWantTo">Do you want to / 너 ~하고 싶어?</option>
      <option value="IWantYouTo">I want you to~ / 나는 네가 ~했으면 좋겠어</option>
      <option value="wouldYouLike">Would you like / ~하시겠어요?</option>
      <option value="WouldYouMind">Would you mind ~? / ~해 주실 수 있으실까요?, ~해도 괜찮을까요?(정중)</option>
      <option value="doYouHaveAny">Do you have any / (혹시) ~있나요?</option>
      <option value="iWanna">I wanna / ~하고 싶어</option>
      <option value="imGonna">Im gonna / 나 ~할거야</option>
      <option value="farFrom">far from / ~이 멀리 있어</option>
      <option value="howLong">how long / ~이 얼마나 걸리나요?</option>
    </select>
  </div>
  <div class="control-group">
    <button onclick="generate()">랜덤 문장 보기</button>
    <button class="listen" onclick="generateListen()">랜덤 문장 듣기</button>
  </div>
  <div id="card" style="display: none">
    <p class="korean" id="korean-text"></p>
    <button onclick="displayAnswer()">👉 영어 보기</button>
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

  fetch('/api/sentences.json')
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
  function generateListen() {
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
    let html = `<button class="replay-btn speak" onclick='fnSpeak("${escapeHtml(currentSentence.en)}");'>재생</button><button onclick="sentenceShow()">👉 문장 보기</button>`;
    $englishListen.innerHTML = html;
  }

  function generate() {
    document.getElementById('english-text').style.display = 'none';
    showAnswer = false;

    clearInterval(timer);
    countdown = 10;

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

  function sentenceShow() {
    const explanationElement = document.getElementById('explanation');

    if (!showSentence) {
      showSentence = true;
      let html = `<div class="fs40 mt15">${currentSentence.ko}<br>${currentSentence.en}</div>`;

      if (currentSentence.description) {
        html += `<hr><div class="fw-normal mt15 fs30">📝 ${currentSentence.description}</div>`;
      }
      if (currentSentence.talk) {
        html += `<hr<div class="fw-normal mt15 fs30"><strong>Situation:</strong> ${currentSentence.talk.situation}</div>`;
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

  function displayAnswer() {
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
