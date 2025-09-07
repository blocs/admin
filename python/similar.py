# Process::input(json_encode())->run(['python', 'similar.py', database_path(), $name, $scoreThreshold, $docsLimit])

import sys
import os
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

# ドキュメントを検索
score_threshold = float(sys.argv[3])
docs_limit = int(sys.argv[4])
retriever = vectorStore.as_retriever(
    search_type="similarity_score_threshold",
    search_kwargs={
        "score_threshold": score_threshold,
        "k": docs_limit,
    },
)
documents = retriever.invoke(input().strip())

# documentsのすべての要素のpage_contentを取得
for i in range(len(documents)):
    print(documents[i].page_content)
