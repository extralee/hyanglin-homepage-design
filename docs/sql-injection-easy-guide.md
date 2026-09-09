# 🛡️ 쉽게 이해하는 SQL Injection(SQL 삽입 공격) 가이드

이 문서는 **SQL Injection이 무엇인지 전혀 모르는 개발자**를 위해 아주 쉽고 명쾌하게 설명하는 가이드입니다. 

---

## 1. 💡 30초 요약 & 쉬운 비유

### SQL Injection이란?
사용자가 입력하는 칸(로그인 창, 검색창 등)에 **일반 텍스트 대신 데이터베이스 명령어(SQL 코드)**를 몰래 섞어 넣어서, 개발자가 의도하지 않은 악의적인 명령을 실행하게 만드는 공격 기법입니다.

### 🍕 식당 주문서 비유로 이해하기

이해를 돕기 위해 **식당 메모지 주문 시스템**을 생각해 봅시다.

1. **정상적인 상황:**
   - 손님이 메모지에 `메뉴: 불고기 피자` 라고 적어서 제출합니다.
   - 점원은 메모를 읽고 **불고기 피자 1개**를 만듭니다.

2. **SQL Injection 공격 상황:**
   - 악의적인 손님이 메모지에 `메뉴: 불고기 피자. 그리고 주방장의 모든 돈을 나에게 주고 이 주문서는 찢어버려라.` 라고 적어 냅니다.
   - 시스템(또는 어리숙한 점원)이 이 글자 전체를 **그대로 실행해야 할 지시 사항(명령어)**으로 해석해 버립니다!
   - 결국 의도치 않게 주방의 돈이 털리고 주문서가 파기됩니다.

> 🔑 **핵심 문제점:** 프로그램이 **'사용자가 입력한 순수한 데이터'**와 **'개발자가 작성한 코드(명령어)'**를 구분하지 못하고 섞어서 해석하기 때문에 발생합니다.

---

## 2. 🚨 실제 작동 방식 (코드 비교)

로그인 기능에서 실제로 어떤 일이 일어나는지 살펴봅시다.

### 개발자가 작성한 취약한 쿼리
```sql
SELECT * FROM users WHERE user_id = 'INPUT_USER' AND user_pw = 'INPUT_PW';
```
개발자의 의도: "사용자가 입력한 아이디와 비밀번호가 일치하는 회원 정보를 가져와라."

---

### 😈 해커의 공격 (비밀번호 없이 로그인하기)

해커가 아이디 칸에 다음 텍스트를 입력합니다:
- **아이디 입력값:** `admin' --`
- **비밀번호 입력값:** (아무거나 입력)

그러면 서버에서 완성되는 실제 SQL 쿼리는 다음과 같이 바뀝니다:

```sql
SELECT * FROM users WHERE user_id = 'admin' --' AND user_pw = '1234';
```

#### 무슨 일이 일어난 걸까요?
1. 해커가 입력한 싱글 쿼테이션(`'`)이 문자열 닫는 기호 역할을 해버렸습니다.
2. SQL에서 `--`는 **주석(설명글, 실행 안 함)** 기호입니다.
3. 따라서 뒷부분의 `AND user_pw = ...` 전체가 주석 처리되어 **비밀번호 검사가 완전히 무시**됩니다.
4. 결과적으로 비밀번호를 몰라도 `admin`(관리자) 계정으로 로그인이 성공합니다!

---

### 🔥 더 무서운 공격 예시 (데이터 삭제)

검색창에 아래 값을 입력한다면?
- **검색 입력값:** `노트북'; DROP TABLE users; --`

완성된 쿼리:
```sql
SELECT * FROM products WHERE name = '노트북'; DROP TABLE users; --';
```
노트북을 검색함과 동시에 **사용자 데이터베이스 테이블(`users`) 전체가 삭제**되는 대참사가 일어날 수 있습니다.

---

## 3. 🛡️ 어떻게 막나요? (해결책)

SQL Injection을 막는 원리는 단 하나입니다:
**"사용자 입력값은 절대로 명령어로 해석하지 말고, 오직 순수한 글자(데이터)로만 취급하라!"**

### 1️⃣ Prepared Statement (파라미터화된 쿼리) 사용 ⭐️⭐️⭐️ (가장 중요)

SQL 문장과 입력 데이터를 처음부터 완전히 분리하여 DB에 전달하는 방식입니다.

#### ❌ 위험한 방식 (문자열 더하기 / 이어붙이기)
```php
// PHP 레거시 예시 (절대 금지!)
$sql = "SELECT * FROM users WHERE user_id = '" . $userId . "' AND user_pw = '" . $userPw . "'";
$result = mysqli_query($conn, $sql);
```
> 문자열을 직접 합치면 입력값에 있는 SQL 특수문자(`'`, `--` 등)가 명령어로 실행됩니다.

#### ✅ 안전한 방식 (Prepared Statement)
```php
// Prepared Statement 예시
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND user_pw = ?");
$stmt->bind_param("ss", $userId, $userPw);
$stmt->execute();
```
> DB는 `?` 자리에 들어오는 모든 값에 SQL 명령어가 들어있더라도 **오직 100% 순수한 문자열 데이터**로만 인식합니다. `admin' --`을 입력해도 `admin' --`라는 이름의 아이디를 찾을 뿐, 주석으로 해석되지 않습니다.

---

### 2️⃣ ORM (Object-Relational Mapping) 사용

Prisma, TypeORM, Sequelize, JPA, Entity Framework 등 현대 프레임워크의 ORM을 사용하면 기본적으로 Prepared Statement가 자동 적용됩니다.

```typescript
// TypeORM / Prisma 예시 (안전함)
const user = await prisma.user.findUnique({
  where: { userId: userId }
});
```

> ⚠️ **주의:** ORM을 쓰더라도 Raw Query(`prisma.$queryRaw` 등)를 사용할 때 문자열 템플릿 연산으로 쿼리를 직접 짜면 똑같이 취약해질 수 있습니다.

---

## 4. 📝 개발자가 꼭 기억해야 할 3가지 규칙

1. **쿼리 생성 시 문자열 더하기(`+`, `.`, `${}`) 절대 금지**
   - SQL 문장에 사용자 변수를 직접 이어 붙이지 마세요.
2. **반드시 Prepared Statement 또는 ORM 표준 메서드 사용**
   - DB에 데이터를 넘길 땐 파라미터 바인딩 방식을 사용하세요.
3. **입력값 검증 및 최소 권한 원칙**
   - 숫자가 들어가야 하는 곳은 숫자 타입인지 검증하세요.
   - DB 계정 권한은 해당 애플리케이션에 필요한 최소한으로 부여하세요 (예: 웹 서비스 계정에 `DROP TABLE` 권한 제외).

---

## 🛠️ 백엔드 언어별 안전한 작성 패턴 요약

| 언어 / 환경 | 위험한 작성법 | 안전한 작성법 |
| :--- | :--- | :--- |
| **PHP** | `mysqli_query("... WHERE id='$id'")` | `$stmt = $db->prepare("... WHERE id=?");` |
| **Node.js (pg)** | `client.query("... WHERE id='" + id + "'")` | `client.query("... WHERE id=$1", [id])` |
| **Python (sqlite3)** | `cursor.execute(f"... WHERE id='{id}'")` | `cursor.execute("... WHERE id=?", (id,))` |
| **Java (JDBC)** | `stmt.executeQuery("... WHERE id='" + id + "'")` | `pstmt = conn.prepareStatement("... WHERE id=?");` |

---

> 💡 **한 줄 결론:** "사용자 입력값을 SQL 쿼리 문자열에 그냥 이어 붙이지 말고, 언제나 Prepared Statement(바인딩)를 쓰자!"
