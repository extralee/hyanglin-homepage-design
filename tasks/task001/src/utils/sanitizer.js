/**
 * 텍스트 내의 민감정보(전화번호, 이메일)를 마스킹합니다.
 * 실명은 보존하기 위해 별도의 이름 마스킹은 수행하지 않습니다.
 */
function sanitizeText(text) {
  if (!text) return '';

  // 1. 전화번호 마스킹 (010-1234-5678, 02-123-4567, 01012345678 등)
  // 하이픈이 있거나 없는 한국 전화번호 패턴
  let sanitized = text.replace(/(01[016789]|0[2-9][0-9]?)-?([0-9]{3,4})-?([0-9]{4})/g, (match, p1, p2, p3) => {
    return `${p1}-****-****`;
  });

  // 2. 이메일 마스킹 (test@example.com -> te***@example.com)
  sanitized = sanitized.replace(/([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/g, (match, p1, p2) => {
    const maskedId = p1.length > 2 ? p1.substring(0, 2) + '***' : p1.substring(0, 1) + '***';
    return `${maskedId}@${p2}`;
  });

  return sanitized;
}

// 직접 실행했을 때 테스트를 수행하도록 분기
if (require.main === module) {
  const sampleText = `
안녕하세요 홍길동입니다. 
제 전화번호는 010-1234-5678 이고, 옛날 번호는 011-987-6543 입니다. 
붙여쓴 번호 01011112222 도 있어요. 일반 전화 02-123-4567 도 마스킹 됩니다.
이메일은 test_user123@hyanglin.org 로 보내주세요.
실명 홍길동, 김철수, 이영희는 그대로 남아있어야 합니다.
  `;

  console.log("=== 원본 텍스트 ===");
  console.log(sampleText);
  console.log("=== 마스킹된 텍스트 ===");
  console.log(sanitizeText(sampleText));
}

module.exports = { sanitizeText };
