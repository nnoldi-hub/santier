export function pluralize(count, singular, plural) {
    const n = Number(count) || 0;
    return `${n} ${n === 1 ? singular : plural}`;
}
