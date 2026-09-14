const pad2 = (value) => String(value).padStart(2, "0");

const formatYear = (year) => {
  const text = String(year);
  return text.length === 2 ? `20${text}` : text.padStart(4, "0");
};

const formatDateParts = (day, month, year) =>
  `${pad2(day)}-${pad2(month)}-${formatYear(year)}`;

const formatTimeParts = (hour, minute, second) => {
  if (hour === undefined || minute === undefined) {
    return "";
  }

  return [hour, minute, second].filter(Boolean).map(pad2).join(":");
};

export function formatDisplayDate(value, fallback = "N/A") {
  if (!value) {
    return fallback;
  }

  const text = String(value).trim();

  const isoMatch = text.match(
    /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/
  );
  if (isoMatch) {
    return formatDateParts(isoMatch[3], isoMatch[2], isoMatch[1]);
  }

  const indianMatch = text.match(
    /^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/
  );
  if (indianMatch) {
    return formatDateParts(indianMatch[1], indianMatch[2], indianMatch[3]);
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return text || fallback;
  }

  return formatDateParts(
    parsedDate.getDate(),
    parsedDate.getMonth() + 1,
    parsedDate.getFullYear()
  );
}

export function formatDisplayDateTime(value, fallback = "N/A") {
  if (!value) {
    return fallback;
  }

  const text = String(value).trim();

  const isoMatch = text.match(
    /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/
  );
  if (isoMatch) {
    const formattedDate = formatDateParts(isoMatch[3], isoMatch[2], isoMatch[1]);
    const formattedTime = formatTimeParts(isoMatch[4], isoMatch[5], isoMatch[6]);

    return formattedTime ? `${formattedDate} ${formattedTime}` : formattedDate;
  }

  const indianMatch = text.match(
    /^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/
  );
  if (indianMatch) {
    const formattedDate = formatDateParts(indianMatch[1], indianMatch[2], indianMatch[3]);
    const formattedTime = formatTimeParts(
      indianMatch[4],
      indianMatch[5],
      indianMatch[6]
    );

    return formattedTime ? `${formattedDate} ${formattedTime}` : formattedDate;
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return text || fallback;
  }

  return `${formatDateParts(
    parsedDate.getDate(),
    parsedDate.getMonth() + 1,
    parsedDate.getFullYear()
  )} ${pad2(parsedDate.getHours())}:${pad2(parsedDate.getMinutes())}:${pad2(
    parsedDate.getSeconds()
  )}`;
}
