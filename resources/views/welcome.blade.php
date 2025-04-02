<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>랜덤 영어 생성기</title>
<link rel="stylesheet" href="/css/common.css?v=20250327">
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
      <option value="itCosts">It costs~ / ~하는 데 돈이 든다</option>
      <option value="areYouGoingTo">are you going to ~?  / ~할 거예요?</option>
      <option value="ImReadyTo">I'm ready to ~ / ~할 준비가 됐어, 이제 ~할 수 있어</option>
      <option value="itTakes">It takes ~ / ~하는 데 (시간)이 걸리다</option>
      <option value="someThings">something, somewhere, someone / 무언가, 어떤 장소, 누군가</option>
      <option value="ImJustGoingTo">I think I'm just going to~ / 내 생각에는 그냥 ~할 것 같아 / 나는 그냥 ~하려고 해</option>
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
    <p class="english" id="english-text">
      <span></span>
    </p>
  </div>
  <div id="careListen" style="display: none">
    <p class="listen" id="english-listen">👉 듣기</p>
    <!-- <button onclick="sentenceShow()">👉 문장 보기</button> -->
    <p class="english" id="explanation">
      <span></span>
    </p>
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
  const $careListen = document.getElementById('careListen');
  const $card = document.getElementById('card');

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

    $careListen.style.display = 'block';
    $card.style.display = 'none';
    const $englishListen = document.getElementById('english-listen');
    let html = `<button class="replay-btn" onclick="speak()">재생</button><button onclick="sentenceShow()">👉 문장 보기</button>`;
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
    $careListen.style.display = 'none';
  }

  function sentenceShow() {
    const explanationElement = document.getElementById('explanation');

    if (!showSentence) {
      showSentence = true;
      let html = `${currentSentence.ko}<br>${currentSentence.en}`;
  
      if (currentSentence.description) {
        html += `<br><small style="font-size:22px; color:#555;">📝 ${currentSentence.description}</small>`;
      }

      explanationElement.querySelector('span').innerHTML = html;
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
      let html = `${currentSentence.en} <button class="replay-btn" onclick="speak()">🔊</button>`;

      if (currentSentence.description) {
        html += `<br><small style="font-size:22px; color:#555;">📝 ${currentSentence.description}</small>`;
      }

      englishTextElement.querySelector('span').innerHTML = html;
      englishTextElement.style.display = 'block';
    } else {
      englishTextElement.style.display = 'none';
      showAnswer = false;
    }
  }

  function speak() {
    const utterance = new SpeechSynthesisUtterance(currentSentence.en);
    utterance.lang = 'en-US';
    utterance.rate = speechRate;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    const maleVoice = voices.find(v => v.lang.startsWith('en') && /male|daniel|alex/i.test(v.name));
    utterance.voice = maleVoice || voices.find(v => v.lang.startsWith('en'));

    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
  }
</script>
</body>
</html>