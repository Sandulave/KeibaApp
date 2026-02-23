export function payoutRows(betType, existingRows) {
  return {
    betType,
    rows: Array.isArray(existingRows) ? existingRows : [],

    init() {
      // rowsが空なら券種ごとの初期行数を入れる（好みで変更OK）
      if (this.rows.length === 0) {
        const initial =
          (this.betType === 'fukusho' || this.betType === 'wide') ? 3 : 1;

        for (let i = 0; i < initial; i++) {
          this.rows.push({ selection_key: '', payout_per_100: '', popularity: '' });
        }
      } else {
        // popularityが無い古いデータに備えて補完
        this.rows = this.rows.map(r => ({
          selection_key: r.selection_key ?? '',
          payout_per_100: r.payout_per_100 ?? '',
          popularity: r.popularity ?? '',
        }));
      }
    },

    addRow() {
      this.rows.push({ selection_key: '', payout_per_100: '', popularity: '' });
    },

    removeRow(i) {
      if (this.rows.length > 1) this.rows.splice(i, 1);
    },
  };
}
