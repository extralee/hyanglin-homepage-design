const TurndownService = require('turndown');

// 기본 설정: headings(h1~h6), 굵은 글씨 등은 기본 유지
const turndownService = new TurndownService({
  headingStyle: 'atx',
  codeBlockStyle: 'fenced'
});

// 깨진 이미지나 쓸모없는 빈 문단 태그 처리 규칙 추가
turndownService.addRule('removeEmptyParagraphs', {
  filter: function (node, options) {
    return node.nodeName === 'P' && node.textContent.trim() === '' && node.childNodes.length === 0;
  },
  replacement: function (content) {
    return '';
  }
});

// XE 에디터 등에서 많이 나오는 불필요한 공백/태그 제거용 (필요시 확장)

/**
 * HTML 문자열을 Markdown으로 변환합니다.
 */
function convertHtmlToMarkdown(html) {
  if (!html) return '';
  return turndownService.turndown(html);
}

// 직접 실행했을 때 테스트를 수행하도록 분기
if (require.main === module) {
  const sampleHtml = `
    <div style="color: red; font-family: dotum;">
      <p>안녕하세요. <strong>강조된 텍스트</strong>입니다.</p>
      <p></p>
      <p>이것은 두 번째 문단입니다.</p>
      <span style="font-size: 14px;">불필요한 스타일이 적용된 텍스트</span>
      <img src="/broken/link.jpg" alt="깨진 이미지">
      <br>
      <a href="http://example.com">외부 링크</a>
    </div>
  `;

  console.log("=== 원본 HTML ===");
  console.log(sampleHtml);
  console.log("\n=== 변환된 마크다운 ===");
  console.log(convertHtmlToMarkdown(sampleHtml));
}

module.exports = { convertHtmlToMarkdown };
