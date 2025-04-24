import os
from dotenv import load_dotenv

import mysql.connector
from youtube_transcript_api import YouTubeTranscriptApi

import re
import sys
import json
from datetime import datetime

_domain = 'https://watvmedia.org'
_mysql_host = ""
_mysql_user = ""
_mysql_password = ""

def log_error(message):
    """
    오류 메시지를 error.log 파일에 기록합니다.
    Parameters:
        message (str): 기록할 오류 메시지
    """
    current_date = datetime.now().strftime("%Y-%m-%d")

    # 로그 디렉토리 설정
    log_dir = os.path.join(os.path.dirname(__file__), "../python")
    os.makedirs(log_dir, exist_ok=True)  # 디렉토리가 없으면 생성

    # 날짜가 포함된 파일 이름
    log_file_name = f"{current_date}-error.log"
    log_file_path = os.path.join(log_dir, log_file_name)

    with open(log_file_path, "a") as log_file:
        log_file.write("\n")
        log_file.write(datetime.now().strftime("%Y-%m-%d %H:%M:%S") + " - ")
        log_file.write(message)

def set_watv_media_connection():
    """
    MariaDB 연결 객체를 반환합니다.
    """
    return mysql.connector.connect(
        host= _mysql_host,
        user= _mysql_user,
        password=_mysql_password,
        database="WATV_MEDIA"
    )
def set_watv_wai_connection():
    """
    MariaDB 연결 객체를 반환합니다.
    """
    return mysql.connector.connect(
        host= _mysql_host,
        user= _mysql_user,
        password=_mysql_password,
        database="WATV_WAI"
    )

def test_mariadb_connection():
    try:
        # MariaDB 연결 설정
        conn = set_watv_wai_connection()

        if conn.is_connected():
            print("MariaDB 연결에 성공했습니다!")
        else:
            print("MariaDB 연결에 실패했습니다.")
    except mysql.connector.Error as err:
        print(f"MariaDB 연결 중 오류 발생: {err}")
    finally:
        # 연결 닫기
        if 'conn' in locals() and conn.is_connected():
            conn.close()
            print("MariaDB 연결을 닫았습니다.")

def fetch_media_list(language_gb_sn):
    """
    처리해야 할 미디어 목록을 가져옵니다.
    ko,en,es,pt_PT,pt,ru,ja,hi,ne,mn
    """
    try:
        conn = set_watv_media_connection()
        cursor = conn.cursor(dictionary=True)
        # 조회 쿼리 실행
        query = """
            SELECT a.MEDIA_NO
                 , a.TITLE
                 , a.PLAY_TIME
                 , a.LANGUAGE_GB_SN
                 , a.MEDIA_PATH_NM
                 , a.EMBED_YOUTUBE_URL
                 , a.EMBED_YOUTUBE_URL_FULL
              FROM WATV_MEDIA.T_WATV_MEDIA a
<<<<<<< HEAD
             WHERE watv_media_nm = '설교'
               AND EMBED_YOUTUBE_URL IS NOT NULL
               AND DEL_YN = 0
               AND OPEN_YN = 1
               AND OPEN_DT <= NOW()
               AND MAIN_SERVICE_GB = 2
               AND script_txt IS NULL
               AND a.LANGUAGE_GB_SN = 'mn'
=======
            WHERE watv_media_nm = '설교'
              AND EMBED_YOUTUBE_URL IS NOT NULL
              AND DEL_YN = 0
              AND OPEN_YN = 1
              AND OPEN_DT <= NOW()
              AND MAIN_SERVICE_GB = 2
              AND script_txt IS NULL
              AND a.LANGUAGE_GB_SN = '?'
>>>>>>> 39ca9ccbe3a156f0bd46842f89bf424265de997a
            ORDER BY MEDIA_NO DESC
            LIMIT 50
        """
        cursor.execute(query, (language_gb_sn,))
        result = cursor.fetchall()
        return result
    except mysql.connector.Error as e:
        print(f"MariaDB 연결 중 오류 발생: {e}")
        return []

# def get_video_id(url):
#     """
#     유튜브 URL에서 영상 ID 추출
#     """

#     # embed URL 형식
#     video_id_match = re.search(r"embed/([a-zA-Z0-9_-]{11})", url)
#     if video_id_match:
#         return video_id_match.group(1)

#     # 일반 URL 형식
#     video_id_match = re.search(r"v=([a-zA-Z0-9_-]{11})", url)
#     if video_id_match:
#         return video_id_match.group(1)

#     raise ValueError("올바른 유튜브 URL이 아닙니다. 영상 ID를 찾을 수 없습니다.")

def convert_seconds_to_timestamp(seconds):
    """
    초 단위를 HH:MM:SS 형식으로 변환
    """
    hours = int(seconds // 3600)
    minutes = int((seconds % 3600) // 60)
    seconds = int(seconds % 60)
    return f"{hours:02}:{minutes:02}:{seconds:02}"

def remove_noise_and_onomatopoeia(text):
    """
    텍스트에서 불필요한 대괄호 안의 내용과 단독 의성어를 제거하며, 텍스트가 없는 시간 줄도 제거합니다.
    """
    # 의성어 및 불필요한 텍스트 목록
    onomatopoeia = [
        "woo", "아", "아 으", "아 아 아 으", "오오오", "아", "으", "으 아", "으 으",
        "오", "우", "에", "이", "on 아", "아아아", "to a", "222", "하하", "tools",
        "you", "oo 아", "오 오 오 오 오 오오", "예 오", "아 아 아 아", "으 으 르 아"
    ]

    # 줄 단위로 분리
    lines = text.split('\n')
    cleaned_lines = []

    for i, line in enumerate(lines):
        # [00:00:00] 시간값이 포함된 첫 번째 줄은 무조건 건너뜀
        if re.match(r"\[00:00:00\]", line):
            continue

        # 대괄호 안의 내용 제거 | 예: [음악]
        cleaned_line = re.sub(r"\[.*?\]", "", line).strip()

        # 불필요한 텍스트(의성어나 빈 줄) 제거
        if cleaned_line in onomatopoeia or cleaned_line == "":
            continue

        # 텍스트가 제거되어 시간이 단독으로 남는 경우를 제거
        if re.match(r"\[\d{2}:\d{2}:\d{2}\]$", line):
            continue

        # 정리된 줄 추가
        cleaned_lines.append(line)

    return "\n".join(cleaned_lines)

def fetch_transcript(video_id, language_gb_sn):
    """
    유튜브 영상 ID와 언어 코드를 이용해 자막 데이터를 가져옵니다.
    """
    try:
        # 자막 데이터 가져오기
        transcript = YouTubeTranscriptApi.get_transcript(video_id, languages=[language_gb_sn, 'en', 'auto'])

        # 시간과 텍스트 포함, 텍스트가 없는 경우 제외
        script_text = "\n".join([
            f"[{convert_seconds_to_timestamp(entry['start'])}] {cleaned_text}"
            for entry in transcript
            if (cleaned_text := remove_noise_and_onomatopoeia(entry['text'])) # 텍스트가 비어있지 않을 경우
        ])

        return script_text
    except Exception as e:
        # with open("error.log", "a") as log_file:
        #     log_file.write("\n")
        #     log_file.write(f"### [MEDIA_YOUTUBE_URL : {video_id}] 자막을 가져오는 중 오류 발생\n")
        #     log_file.write(f"{e}\n")
        # print(f"자막을 가져오는 중 오류 발생 (EMBED_YOUTUBE_URL : {video_id}): {e}")
        return None

def clean_transcript(text):
    """
    텍스트에서 [음악]과 같은 불필요한 텍스트를 제거하고 정리된 텍스트를 반환합니다.
    """
    lines = text.split('\n')  # 줄 단위로 분리
    cleaned_lines = []

    for line in lines:
        # [음악]과 같이 대괄호 안의 내용을 제거
        cleaned_line = re.sub(r"\[.*?\]", "", line).strip()

        # 비어 있는 줄은 제외
        if cleaned_line:
            cleaned_lines.append(cleaned_line)

    return "\n".join(cleaned_lines)

def check_if_content_exists(video_id, language_gb_sn):
    """
    데이터베이스에 content_id가 존재하는지 확인합니다.
    """
    try:
        conn = set_watv_wai_connection()
        cursor = conn.cursor()

        query = """
            SELECT COUNT(*)
              FROM WATV_WAI.crawled_contents
             WHERE content_id = %s
               AND domain = %s
               AND language_gb_sn = %s
        """
        cursor.execute(query, (video_id, _domain, language_gb_sn))
        count = cursor.fetchone()[0]
        return count > 0  # 존재하면 True, 아니면 False

    except mysql.connector.Error as e:
        message = f"MariaDB 연결 중 오류 발생: {e}"
        log_error(message)
        return False
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

def update_script_txt(media_no, script_text):
    """
    MariaDB에서 해당 MEDIA_NO의 script_txt 필드를 업데이트합니다.
    """
    try:
        conn = set_watv_media_connection()
        cursor = conn.cursor()

        update_query = """
            UPDATE WATV_MEDIA.T_WATV_MEDIA
            SET script_txt = %s
            WHERE MEDIA_NO = %s
        """
        cursor.execute(update_query, (script_text, media_no))
        conn.commit()

    except mysql.connector.Error as e:
        print(f"MariaDB 업데이트 중 오류 발생: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

def save_content_to_db(video_id, title, url, play_time, language_gb_sn, content):
    """
    데이터를 데이터베이스에 저장합니다.
    """
    try:
        conn = set_watv_wai_connection()
        cursor = conn.cursor()
        thumbnail = "https://i.ytimg.com/vi/"+video_id+"/maxresdefault.jpg"

        query = """
            INSERT INTO WATV_WAI.crawled_contents (domain, language_gb_sn, gubun, content_id, url, play_time, thumbnail, title, content)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(query, (_domain, language_gb_sn, "youtube", video_id, url, play_time, thumbnail, title, content))
        conn.commit()
    except mysql.connector.Error as e:
        message = f"MariaDB 저장 중 오류 발생: {e}"
        print(message)
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

def process_and_update_scripts(language_gb_sn):
    """
    전체 프로세스를 실행하고 JSON 형태의 결과를 반환합니다.
    """
    # 미디어 데이터 가져오기
    result = {
        "language": "",
        "processed": 0,
        "skipped": 0,
        "failed": 0,
        "total": 0
    }
    media_list = fetch_media_list(language_gb_sn)
    result["total"] = len(media_list)
    if not media_list:
        message = {"status": "empty", "message": "처리할 미디어가 없습니다."}
        print(json.dumps(message, indent=4, ensure_ascii=False))

    for media in media_list:
        media_no        = media["MEDIA_NO"]
        media_path_nm   = media["MEDIA_PATH_NM"]
        language_gb_sn  = media["LANGUAGE_GB_SN"]
        title           = media["TITLE"]
        youtube_url     = _domain +'/'+ language_gb_sn + '/media/' + media_path_nm
        play_time       = media["PLAY_TIME"]
        video_id        = media["EMBED_YOUTUBE_URL"]
        youtube_embed_url = media["EMBED_YOUTUBE_URL_FULL"]

        try:
            result["language"] = language_gb_sn
            # video_id = get_video_id(youtube_url) # 유튜브 영상 ID 추출
            if check_if_content_exists(video_id, language_gb_sn):
                #print(f"이미 저장된 컨텐츠: {title} ({video_id}) ({_domain})")
                message = f"media_no : {media_no}, status : skipped, message : 이미 저장된 컨텐츠: {title} ({video_id}) ({_domain})"
                log_error(message)
                result["skipped"] += 1
                continue

            transcript_text = fetch_transcript(video_id, language_gb_sn) # 자막 데이터 가져오기

            if transcript_text:
                update_script_txt(media_no, transcript_text)
                save_content_to_db(video_id, title, youtube_url, play_time, language_gb_sn, transcript_text)
                result["processed"] += 1
            else:
                message = f"media_no : {media_no}, language_gb_sn: {language_gb_sn}, url: {youtube_url}, embed_url: {youtube_embed_url}, status : failed, message : 자막 없음 또는 비어 있음."
                log_error(message)
                result["failed"] += 1
                update_script_txt(media_no, "자막없음")
                continue
        except ValueError as ve:
            message = f"media_no : {media_no}, status : error, message : 유튜브 URL 처리 실패: {ve}"
            log_error(message)
            result["failed"] += 1
            continue
        except Exception as e:
            message = f"media_no : {media_no}, status : error, message : {str(e)}"
            log_error(message)
            result["failed"] += 1
            continue

    print(json.dumps(result, indent=4, ensure_ascii=False))

if __name__ == "__main__":
    _mysql_host = sys.argv[1]  # 첫 번째 인수
    _mysql_user = sys.argv[2]  # 두 번째 인수
    _mysql_password = sys.argv[3]  # 세 번째 인수
    language_gb_sn = sys.argv[4]  # 네 번째 인수
    # _mysql_host = "172.17.130.131"
    # _mysql_user = "user_for_watv"  # 두 번째 인수
    # _mysql_password = "user_for_watv"  # 세 번째 인수

    process_and_update_scripts(language_gb_sn)

    # 유튜브 영상 URL 및 언어 코드 입력
    # video_url = input("유튜브 영상 URL을 입력하세요: ")
    # language_code = input("언어 코드를 입력하세요 (예: ko, en): ")
    # fetch_transcript_with_timestamps(video_url, language_code)

