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
    <input type="range" id="rate" min="0.5" max="1.5" step="0.05" value="0.85">
    <span id="rate-value">0.85</span>
  </div>
  <div class="control-group">
  <select id="group" onchange="updateSelectedGroup(this.value)">
      <option value="all">전체</option>
      <option value="itTakes">It takes ~ / ~하는 데 (시간)이 걸리다</option>
      <option value="someThings">something, somewhere, someone / 무언가, 어떤 장소, 누군가</option>
      <option value="imJustAboutTo">I'm just about to~ / 막 ~하려던 참이야</option>
      <option value="itsGetting">It's getting / 점점~해지고 있어</option>
      <option value="isIt">is it / ~인가요?</option>
      <option value="imTryingTo">I'm trying to ~ / ~하려고 노력 중이야</option>
      <option value="thereIsAre">There is~, There are~ / ~이 있다, ~들이 있다</option>
      <option value="iJustWantedTo">I just wanted to / 나는 단지 ~ 하고 싶었어</option>
      <option value="doIHaveTo">Do I have to~? / 내가 ~해야 하나요?</option>
      <option value="doYouWantTo">Do you want to / 너 ~하고 싶어?</option>
      <option value="wouldYouLike">Would you like / ~하시겠어요?</option>
      <option value="doYouHaveAny">Do you have any / (혹시) ~있나요?</option>
      <option value="iWanna">I wanna / ~하고 싶어</option>
      <option value="imGonna">Im gonna / 나 ~할거야</option>
      <option value="farFrom">far from / ~이 멀리 있어</option>
      <option value="howLong">how long / ~이 얼마나 걸리나요?</option>
    </select>
    <button onclick="generateAndSpeak()">문장 듣기</button>
    <button onclick="displayAnswer()">👉 영어 보기</button>
  </div>
  <div id="card" style="display: none">
    <p class="korean" id="korean-text"></p>
    <div id="sentence-details" style="display: none;">
      <p class="english" id="english-text"></p>
      <p class="description" id="description-text"></p>
    </div>
  </div>
</div>

<script>
let selectedGroup = 'all';
let sentences = {};
let usedSentences = new Set();
let currentSentence = null;
let showAnswer = false;
let speechRate = 0.85;
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

function generate() {
    document.getElementById('sentence-details').style.display = 'none';
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
    document.getElementById('card').style.display = 'block';
}

function speak() {
    if (!currentSentence) {
        alert('먼저 문장을 생성해주세요!');
        return;
    }
    const utterance = new SpeechSynthesisUtterance(currentSentence.en);
    utterance.lang = 'en-US';
    utterance.rate = speechRate;
    speechSynthesis.speak(utterance);
}

function displayAnswer() {
    const detailsDiv = document.getElementById('sentence-details');
    if (!showAnswer) {
        showAnswer = true;
        document.getElementById('english-text').textContent = currentSentence.en;
        document.getElementById('description-text').textContent = currentSentence.description;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
        showAnswer = false;
    }
}

</script>
</body>
</html>
