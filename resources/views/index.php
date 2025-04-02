<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>랜덤 영어 생성기</title>
<style>
  * {
    box-sizing: border-box;
  }

  html, body {
    margin: 0;
    padding: 0;
    width: 100vw;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    background: #fff;
    font-size: 26px; /* ⬆️ 전체 글자 큼 */
  }

  .container {
    width: 100vw;
    padding: 24px 16px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 40px;
  }

  h2 {
    font-size: 38px;
    font-weight: bold;
    text-align: center;
    margin: 0;
  }

  .voice-control,
  .control-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
  }

  label {
    font-size: 28px;
    font-weight: 600;
  }

  input[type="range"] {
    width: 100%;
    height: 50px;
  }

  select,
  button {
    width: 100%;
    padding: 20px;
    font-size: 28px;
    border: 1px solid #ccc;
    border-radius: 14px;
  }

  button {
    background-color: #2b7cff;
    color: white;
    font-weight: bold;
    cursor: pointer;
  }

  button:disabled {
    background-color: #999;
    cursor: not-allowed;
  }

  .card {
    border: 2px solid #ddd;
    padding: 28px;
    border-radius: 16px;
    background-color: #f9f9f9;
    width: 100%;
  }

  .korean,
  .timer,
  .ready,
  .english {
    font-size: 30px;
    margin-bottom: 20px;
    line-height: 1.7;
  }

  .english {
    font-weight: bold;
    display: none;
  }

  .replay-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 36px;
    margin-left: 16px;
  }

  .replay-btn:hover {
    color: #007bff;
  }

  /* 💻 데스크탑 스타일 */
  @media (min-width: 768px) {
    .container {
      max-width: 700px;
      margin: 0 auto;
      padding: 40px;
    }

    .voice-control,
    .control-group {
      flex-direction: row;
      align-items: center;
    }

    .voice-control label,
    .control-group label {
      width: 160px;
    }

    select,
    input[type="range"],
    button {
      flex: 1;
      width: auto;
    }

    button {
      max-width: 260px;
    }
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
    <label for="group">그룹 선택:</label>
    <select id="group" onchange="updateSelectedGroup(this.value)">
      <option value="all">전체</option>
      <option value="farFrom">far from</option>
      <option value="howLong">how long</option>
      <option value="iWanna">I wanna</option>
      <option value="imGonna">Im gonna</option>
      <option value="wouldYouLike">Would you like</option>
      <option value="doYouWantTo">Do you want to</option>
      <option value="doYouHaveAny">Do you have any</option>
    </select>
    <button onclick="generate()">랜덤 문장 보기</button>
  </div>
  <div id="card" style="display: none">
    <p class="korean" id="korean-text"></p>
    <p class="timer" id="timer-text"></p>
    <p class="ready" id="ready-text" style="display: none">✅ 이제 정답을 확인해보세요!</p>
    <button onclick="displayAnswer()">👉 영어 보기</button>
    <p class="english" id="english-text">
      <span></span>
      <button class="replay-btn" onclick="speak()" style="display: none;">🔊</button>
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

  fetch('/api/sentences.json').then(response => response.json()).then(data => {
    sentences = data;
  });

  function updateSelectedGroup(value) {
    selectedGroup = value;
  }

  function generate() {
    document.getElementById('english-text').style.display = 'none';
    document.getElementById('english-text').querySelector('.replay-btn').style.display = 'none';
    showAnswer = false;

    clearInterval(timer);
    countdown = 10;

    let pool = selectedGroup === 'all' ? [].concat(...Object.values(sentences)) : sentences[selectedGroup] || [];

    const filteredPool = pool.filter(s => !usedSentences.has(s.ko));
    if (filteredPool.length === 0) {
      alert('All sentences from this group have been shown. Resetting...');
      usedSentences.clear();  // Reset used sentences if all have been shown
      return;
    }

    const random = filteredPool[Math.floor(Math.random() * filteredPool.length)];
    currentSentence = random;
    usedSentences.add(random.ko);

    document.getElementById('korean-text').textContent = random.ko;
    document.getElementById('timer-text').textContent = '🕒 ' + countdown + '초 안에 영어로 말해보세요!';
    document.getElementById('card').style.display = 'block';
    timer = setInterval(() => {
      countdown--;
      if (countdown <= 0) {
        clearInterval(timer);
        document.getElementById('timer-text').style.display = 'none';
        document.getElementById('ready-text').style.display = 'block';
      } else {
        document.getElementById('timer-text').textContent = '🕒 ' + countdown + '초 안에 영어로 말해보세요!';
      }
    }, 1000);
  }

  function displayAnswer() {
    if (!showAnswer) {
      showAnswer = true;
      const englishTextElement = document.getElementById('english-text');
      englishTextElement.querySelector('span').textContent = currentSentence.en;
      englishTextElement.style.display = 'block';
      englishTextElement.querySelector('.replay-btn').style.display = 'inline-block'; // Ensure the replay button is visible
    } else {
      document.getElementById('english-text').style.display = 'none';
      document.getElementById('english-text').querySelector('.replay-btn').style.display = 'none';
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
    if (maleVoice) {
      utterance.voice = maleVoice;
    } else {
      utterance.voice = voices.find(v => v.lang.startsWith('en'));
    }

    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
  }
</script>
</body>
</html>
