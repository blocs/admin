# Process::input(json_encode())->run(['python', 'delete.py', database_path(), $name)

import sys
import json
import os
from langchain_core.documents import Document
from langchain_chroma import Chroma
from langchain_openai import OpenAIEmbeddings

# 永続化ディレクトリの設定
persist_directory = sys.argv[1] + "/chroma_db"
os.makedirs(persist_directory, exist_ok=True)

# VectorStoreの作成
embeddings = OpenAIEmbeddings(
    model="text-embedding-ada-002"
)

# VectorStoreを読み込み
vectorStore = Chroma(
    embedding_function=embeddings,
    persist_directory=persist_directory,
    collection_name=sys.argv[2],
)

# JSONファイルの読み込み
stdin = json.loads(input().strip())

if len(stdin):
    for doc_id in stdin:
        # 既存のドキュメントをIDで検索
        existing_docs = vectorStore.get(
            where={"id": doc_id}
        )

        # 既存のドキュメントを削除
        if existing_docs and existing_docs['documents']:
            # 既存のドキュメントがある場合は削除してから追加（更新）
            vectorStore.delete(
                where={"id": doc_id}
            )
else:
    # 空の入力の場合、vectorStoreの全ドキュメントを削除
    all_docs = vectorStore.get()
    if all_docs and all_docs['ids']:
        vectorStore.delete(ids=all_docs['ids'])

# 永続化を確実に実行
vectorStore.persist()
