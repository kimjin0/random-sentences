<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>랜덤 영어 생성기</title>
<link rel="stylesheet" href="/css/common.css?v=20250327">
</head>
<body>
<div class="container">
  <div class="slide">
    <ul class="list">
      <li >
        <a href="/two-mice">two mice</a>
    </li>      
      <li class="active">
          <a href="/marketplace">marketplace</a>
      </li>

    </ul>
  </div>
  </div>  <div class="voice-control" style="margin-bottom: 1em;">
    <button id="readAllButton" style="font-size: 0.8em;">전체 문장 듣기</button>
    <label for="repeatCount">반복 횟수:</label>
    <select id="repeatCount" style="font-size: 0.8em;">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
    </select>
</div>
<div class="control-group">
    <label for="rate">말하기 속도:</label>
    <input type="range" id="rate" min="0.5" max="1.5" step="0.05">
    <span id="rate-value">0.85</span>
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
  <div class="sentence-container">
    <ul id="sentence-list">
        <!-- Sentences will be dynamically inserted here -->
    </ul>
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
  //랜덤 문장 드기
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

  let currentSentenceIndex = 0;
  let twoMiceSentences = [];

  fetch('/api/marketplace.json')
      .then(response => response.json())
      .then(data => {
          twoMiceSentences = data;
          displayAllSentences();
      });

  function displayAllSentences() {
      const sentenceList = document.getElementById('sentence-list');
      sentenceList.innerHTML = '';
      twoMiceSentences.forEach((sentence, index) => {
          const listItem = document.createElement('li');
          listItem.innerHTML = `${sentence.en} <span style="font-size: 0.5em; color: #888; cursor: pointer;" onclick="toggleKorean(${index})">보기</span> <span style="font-size: 0.5em; color: #888; cursor: pointer;" onclick="toggleExplanation(${index})">설명</span> <span style="font-size: 0.5em; color: #888; cursor: pointer;" onclick="readSentence(${index})">🔊</span> <br> <span id="korean-${index}" style="display: none; color: #007BFF;">${sentence.ko}</span> <span id="explanation-${index}" style="display: none; color: #555;">${sentence.explain || ''}</span>`;
          listItem.style.marginBottom = '1.5em';
          sentenceList.appendChild(listItem);
      });
  }

  function readSentence(index) {
      const sentence = twoMiceSentences[index];
      const utterance = new SpeechSynthesisUtterance(sentence.en);
      utterance.lang = 'en-US'; // Ensure language is set to English
      utterance.rate = speechRate;
      
      const preferredVoice = voices.find(v => v.name.includes('Google UK English Female')) || voices.find(v => v.lang.startsWith('en') && /female|alex/i.test(v.name));
      utterance.voice = preferredVoice || voices.find(v => v.lang.startsWith('en'));
      
      speechSynthesis.cancel();
      speechSynthesis.speak(utterance);
  }

  function toggleKorean(index) {
    const koreanText = document.getElementById(`korean-${index}`);
    if (koreanText.style.display === 'none') {
        koreanText.style.display = 'inline';
    } else {
        koreanText.style.display = 'none';
    }
}

function toggleExplanation(index) {
    const explanationText = document.getElementById(`explanation-${index}`);
    if (explanationText.style.display === 'none') {
        explanationText.style.display = 'inline';
    } else {
        explanationText.style.display = 'none';
    }
}

    document.getElementById('readAllButton').addEventListener('click', function() {
        const repeatCount = parseInt(document.getElementById('repeatCount').value, 10);
        let index = 0;
        function speakNextSentence() {
            if (index < twoMiceSentences.length) {
                const sentence = twoMiceSentences[index];
                let repeat = 0;
                function repeatSentence() {
                    if (repeat < repeatCount) {
                        const utterance = new SpeechSynthesisUtterance(sentence.en);
                        utterance.lang = 'en-US';
                        utterance.rate = parseFloat(speechRate);
                        const preferredVoice = voices.find(v => v.name.includes('Google UK English Female')) || voices.find(v => v.lang.startsWith('en') && /female|alex/i.test(v.name));
                        utterance.voice = preferredVoice || voices.find(v => v.lang.startsWith('en'));
                        utterance.onend = function() {
                            repeat++;
                            if (repeat < repeatCount) {
                                setTimeout(repeatSentence, 1000); // 1 second delay between repeats
                            } else {
                                index++;
                                setTimeout(speakNextSentence, 1000); // 1 second delay between sentences
                            }
                        };
                        speechSynthesis.speak(utterance);
                    }
                }
                repeatSentence();
            }
        }
        speakNextSentence();
    });
</script>
</body>
</html>
