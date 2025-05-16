# 入力画面修正

## 必須入力設定
- input 項目に required をつけます
- コメント内の data-lang で、エラーメッセージを設定します
- エラーメッセージの表示は blade で設定します
- 修正方法は、Example を参考にしてください

### Example
```
<input type='text' name='name' required />
<!-- !name="required" data-lang="必須入力です。" -->
@error('name') <div>{{ $message }}</div> @enderror
```
